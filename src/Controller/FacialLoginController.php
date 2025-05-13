<?php

namespace App\Controller;

// Explicit import for the User entity
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Psr\Log\LoggerInterface;

class FacialLoginController extends AbstractController
{
    #[Route('/facial-login', name: 'app_facial_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        return $this->render('security/facial_login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/facial-login/authenticate', name: 'app_facial_login_authenticate', methods: ['POST'])]
    public function authenticate(
        Request $request, 
        EntityManagerInterface $entityManager, 
        LoggerInterface $logger
    ): Response {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('facial_authenticate', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }
        
        try {
            // Log the raw request content for debugging
            $rawContent = $request->getContent();
            $logger->debug('Raw request content: ' . substr($rawContent, 0, 200) . '...');
            
            // Get data from request
            $data = json_decode($rawContent, true);
            
            // Check if JSON decoding failed
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                $logger->error('JSON decode error: ' . json_last_error_msg());
                return $this->json([
                    'success' => false, 
                    'message' => 'Données JSON invalides: ' . json_last_error_msg()
                ], 400);
            }
            
            if (!isset($data['faceImage'])) {
                return $this->json(['success' => false, 'message' => 'Image du visage manquante'], 400);
            }
            
            $logger->info('Tentative d\'authentification faciale');
            
            // Get the face descriptor directly from the request
            $faceDescriptor = $data['faceDescriptor'] ?? null;
            if (!$faceDescriptor) {
                $logger->warning('Descripteur facial manquant dans la requête');
            } else {
                $logger->debug('Reçu descripteur facial de taille: ' . count($faceDescriptor));
            }
            
            // Process the base64 image
            $imageData = $data['faceImage'];
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            
            // Validate base64 data before decoding
            if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $imageData)) {
                $logger->warning('Données d\'image non valides');
                return $this->json(['success' => false, 'message' => 'Format d\'image invalide'], 400);
            }
            
            $decodedImage = base64_decode($imageData);
            if ($decodedImage === false) {
                $logger->error('Échec du décodage base64 de l\'image');
                return $this->json(['success' => false, 'message' => 'Échec du décodage de l\'image'], 400);
            }
            
            // Find user with facial authentication enabled
            $user = $this->findUserByFaceDescriptor($faceDescriptor, $entityManager, $logger);
            
            if (!$user) {
                $logger->warning('Aucun utilisateur correspondant trouvé pour l\'authentification faciale');
                return $this->json(['success' => false, 'message' => 'Aucun utilisateur correspondant trouvé'], 401);
            }
            
            // Explicit type assertion for static analysis tools
            /** @var User $user */
            
            $logger->info('Utilisateur trouvé pour l\'authentification faciale: ' . $user->getEmail());
            $logger->info('User role in database: ' . $user->getRole());
            $logger->info('User roles after transformation: ' . implode(', ', $user->getRoles()));
            
            // Get the proper security services using autowiring
            $security = $this->container->get('security.token_storage');
            $session = $request->getSession();
            
            // Create the token with correct firewall name ('main')
            $firewallName = 'main';
            $token = new PostAuthenticationToken(
                $user,                // The authenticated user
                $firewallName,        // The firewall name
                $user->getRoles()     // The user's roles
            );
            
            // Set the token in security context
            $security->setToken($token);
            
            // Store the token in the session
            $session->set('_security_'.$firewallName, serialize($token));
            
            // Migrate and save the session
            $session->migrate(true);
            
            // Determine redirect path based on roles
            $redirectPath = '/profile'; // Default path
            if (in_array('ROLE_RESPONSABLE_RH', $user->getRoles())) {
                $redirectPath = '/dashboard';
                $logger->info('User has ROLE_RESPONSABLE_RH, redirecting to dashboard');
            } else {
                $logger->info('User has standard role, redirecting to profile');
            }
            
            // Log authentication success
            $logger->info('Authentication successful for user: ' . $user->getEmail());
            
            // Return response based on request type
            if ($request->isXmlHttpRequest()) {
                // If it's an AJAX request, return JSON with status
                return $this->json([
                    'success' => true, 
                    'message' => 'Authentification réussie', 
                    'email' => $user->getEmail(),
                    'redirectTo' => $redirectPath
                ]);
            } else {
                // If it's a normal request, redirect directly
                return $this->redirect($redirectPath);
            }
        } catch (\Exception $e) {
            $logger->error('Erreur lors de l\'authentification faciale: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // In development environment - return detailed error information
            if ($this->getParameter('kernel.environment') === 'dev') {
                return $this->json([
                    'success' => false, 
                    'message' => 'Erreur lors de l\'authentification', 
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
            
            // In production - return generic error
            return $this->json([
                'success' => false, 
                'message' => 'Erreur lors de l\'authentification, veuillez réessayer'
            ], 500);
        }
    }

    /**
     * Find user by face descriptor
     * Compare the extracted face descriptor with stored descriptors in the database
     * 
     * @param array|null $faceDescriptor Extracted face descriptor
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @return \App\Entity\User|null User object if found, null otherwise
     */
    private function findUserByFaceDescriptor(?array $faceDescriptor, EntityManagerInterface $entityManager, LoggerInterface $logger): ?User
    {
        // Explicit class reference
        $userRepository = $entityManager->getRepository(User::class);
        $users = $userRepository->findBy(['facialAuthEnabled' => true]);
        
        if (empty($users)) {
            $logger->warning('Aucun utilisateur avec authentification faciale activée trouvé');
            return null;
        }
        
        if (!$faceDescriptor) {
            $logger->warning('Descripteur facial absent, impossible de comparer');
            // For demo only - add a configuration check to enable this bypass
            if ($this->getParameter('app.facial_auth.demo_mode') ?? false) {
                $logger->warning('Mode démo activé - authentification sans comparaison de descripteur');
                return $users[0];
            }
            return null;
        }
        
        $bestMatch = null;
        $bestDistance = INF;
        
        foreach ($users as $user) {
            // Explicit type assertion for static analysis tools inside loop
            /** @var User $user */
            
            try {
                $storedData = json_decode($user->getFacialData(), true);
                
                if (!$storedData || !isset($storedData['descriptor'])) {
                    $logger->warning('Utilisateur sans données faciales valides: ' . $user->getEmail());
                    continue;
                }
                
                $storedDescriptor = $storedData['descriptor'];
                
                // Check if stored descriptor is valid
                if (!is_array($storedDescriptor) || count($storedDescriptor) != count($faceDescriptor)) {
                    $logger->warning('Format de descripteur incompatible pour l\'utilisateur: ' . $user->getEmail());
                    continue;
                }
                
                // Calculate distance between descriptors
                $distance = $this->calculateEuclideanDistance($faceDescriptor, $storedDescriptor);
                
                $logger->debug('Distance avec ' . $user->getEmail() . ': ' . $distance);
                
                // Update best match if this user is closer
                if ($distance < $bestDistance) {
                    $bestMatch = $user;
                    $bestDistance = $distance;
                }
            } catch (\Exception $e) {
                $logger->error('Erreur lors de la comparaison des descripteurs: ' . $e->getMessage());
                continue;
            }
        }
        
        // Apply matching threshold
        if ($bestMatch && $bestDistance < 0.6) { // Threshold for match confidence
            $logger->info('Meilleur utilisateur correspondant: ' . $bestMatch->getEmail() . ' (distance: ' . $bestDistance . ')');
            return $bestMatch;
        }
        
        // For development/demo purposes
        if ($this->getParameter('app.facial_auth.demo_mode') ?? false) {
            $logger->warning('Mode démo activé - retour du premier utilisateur sans correspondance précise');
            return $users[0]; 
        }
        
        $logger->warning('Aucun utilisateur correspondant trouvé avec une confiance suffisante');
        return null;
    }
    
    /**
     * Calculate Euclidean distance between two face descriptors
     * Lower distance means higher similarity
     */
    private function calculateEuclideanDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return INF; // Different dimensions, return infinity
        }
        
        $sum = 0;
        foreach ($descriptor1 as $i => $val) {
            $sum += pow($val - $descriptor2[$i], 2);
        }
        
        return sqrt($sum);
    }

    #[Route('/debug-auth', name: 'app_debug_auth')]
    public function debugAuth(LoggerInterface $logger): JsonResponse
    {
        $user = $this->getUser();
        
        // Explicit type assertion for the user to help IDE
        /** @var User|null $user */
        
        $isAuth = $this->isGranted('IS_AUTHENTICATED_FULLY');
        $roleRH = $this->isGranted('ROLE_RESPONSABLE_RH');
        $roleEmployee = $this->isGranted('ROLE_EMPLOYEE');
        
        return $this->json([
            'is_authenticated' => $isAuth,
            'user' => $user ? $user->getEmail() : null,
            'role' => $user ? $user->getRole() : null,
            'symfony_roles' => $user ? $user->getRoles() : [],
            'has_rh_role' => $roleRH,
            'has_employee_role' => $roleEmployee,
        ]);
    }
}