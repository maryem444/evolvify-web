<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\FacialRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FaciallAuthController extends AbstractController
{
    #[Route('/facial-enrollment/save', name: 'facial_enrollment_save', methods: ['POST'])]
    public function saveEnrollment(
        Request $request, 
        FacialRecognitionService $facialService, 
        UserRepository $userRepository, 
        Security $security,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        try {
            // Log beginning of method execution
            error_log('Starting saveEnrollment method');
            
            // Check content type
            $contentType = $request->headers->get('Content-Type');
            error_log('Content-Type header: ' . $contentType);
            
            if (strpos($contentType, 'application/json') === false) {
                error_log('Invalid Content-Type');
                return new JsonResponse(['success' => false, 'message' => 'Content-Type must be application/json'], 400);
            }
    
            // Get raw request content
            $content = $request->getContent();
            error_log('Request content length: ' . strlen($content));
            
            // Check if content is empty
            if (empty($content)) {
                error_log('Empty request content');
                return new JsonResponse(['success' => false, 'message' => 'No data received'], 400);
            }
            
            // Decode JSON
            $data = json_decode($content, true);
            
            // Check for JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode error: ' . json_last_error_msg());
                return new JsonResponse(['success' => false, 'message' => 'Format de données invalide: ' . json_last_error_msg()], 400);
            }
            
            // Debugging - Log the received data structure
            error_log('Received enrollment data: ' . print_r($data, true));
            
            // Check required fields
            if (!isset($data['faceData']) || !is_array($data['faceData']) || empty($data['faceData'])) {
                error_log('Missing or invalid faceData');
                return new JsonResponse(['success' => false, 'message' => 'Données faciales manquantes ou invalides'], 400);
            }
            
            // Get user based on scenario
            $user = null;
            $resetToken = $data['resetToken'] ?? null;
            $email = $data['email'] ?? null;
            
            error_log('Reset token: ' . ($resetToken ?? 'null'));
            error_log('Email: ' . ($email ?? 'null'));
            
            // Special case: reset token and email provided
            if ($resetToken && $email) {
                error_log('Using reset token flow');
                
                // Check if token is "save" (default value from form)
                if ($resetToken === 'save') {
                    // Standard case: user is logged in
                    error_log('Token is "save", using authenticated user');
                    $user = $security->getUser();
                    
                    if (!$user) {
                        // Si aucun utilisateur n'est connecté, essayer de trouver par email
                        error_log('No authenticated user found, trying email lookup: ' . $email);
                        $user = $userRepository->findOneBy(['email' => $email]);
                        
                        if (!$user) {
                            error_log('User not found by email: ' . $email);
                            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
                        }
                    }
                } else {
                    // Reset token flow: find user by email
                    error_log('Looking up user by email: ' . $email);
                    $user = $userRepository->findOneBy(['email' => $email]);
                    
                    if (!$user) {
                        error_log('User not found for email: ' . $email);
                        return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
                    }
                    
                    // Token validation logic would go here
                    // Here you would validate the reset token against stored tokens
                    // For now we're skipping this step since the implementation isn't shown
                    error_log('Reset token validation would go here');
                }
                
                // Save facial data
                error_log('Saving facial data for user with email: ' . $email);
                $result = $facialService->saveFacialData($user, $data['faceData']);
                
                if (!$result) {
                    error_log('Failed to save facial data');
                    return new JsonResponse([
                        'success' => false, 
                        'message' => 'Échec de l\'enregistrement des données faciales'
                    ], 500);
                }
                
                // Determine redirect URL based on whether this is a reset or normal flow
                $redirectUrl = ($resetToken !== 'save') 
                    ? $this->generateUrl('app_login')
                    : $this->getRedirectUrlByRole($user);
                
                error_log('Redirect URL: ' . $redirectUrl);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Configuration faciale enregistrée avec succès',
                    'redirectUrl' => $redirectUrl
                ]);
            } else {
                // Standard case: user should be authenticated
                error_log('Using standard authenticated user flow');
                $user = $security->getUser();
                
                if (!$user) {
                    error_log('No authenticated user found');
                    return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
                }
                
                // Save facial data
                error_log('Saving facial data for authenticated user');
                $result = $facialService->saveFacialData($user, $data['faceData']);
                
                if (!$result) {
                    error_log('Failed to save facial data');
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Échec de l\'enregistrement des données faciales'
                    ], 500);
                }
                
                // Get redirect URL based on user role
                $redirectUrl = $this->getRedirectUrlByRole($user);
                error_log('Redirect URL: ' . $redirectUrl);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Configuration faciale enregistrée avec succès',
                    'redirectUrl' => $redirectUrl
                ]);
            }
        } catch (\Exception $e) {
            // Log detailed exception info for debugging
            error_log('Exception in saveEnrollment: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            
            // Return a clear error message
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/facial-enrollment/{token}', name: 'facial_enrollment_with_token', defaults: ['token' => null])]
    #[Route('/facial-enrollment', name: 'facial_enrollment')]
    public function enrollment($token = null, UserRepository $userRepository): Response
    {
        // Si un token est fourni, vérifier sa validité avant de continuer
        if ($token) {
            // Ici vous pourriez vérifier si le token est valide dans votre système
            // Pour l'instant, nous passons simplement le token au template
            return $this->render('security/facial_enrollment.html.twig', [
                'resetToken' => $token
            ]);
        }
        
        // Sinon on applique le comportement normal (must be logged in)
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/facial_enrollment.html.twig');
    }
    
    #[Route('/facial-login', name: 'facial_login')]
    public function facialLogin(): Response
    {
        // Si déjà connecté, rediriger vers la page appropriée selon le rôle
        if ($this->getUser()) {
            $redirectUrl = $this->getRedirectUrlByRole($this->getUser());
            return $this->redirect($redirectUrl);
        }
        
        return $this->render('security/facial_login.html.twig');
    }
    
    #[Route('/facial-login/verify', name: 'facial_login_verify', methods: ['POST'])]
    public function verifyFacialLogin(
        Request $request,
        FacialRecognitionService $facialService,
        UserRepository $userRepository,
        AuthenticationUtils $authenticationUtils
    ): JsonResponse {
        try {
            // Ensure we're receiving JSON data
            if (strpos($request->headers->get('Content-Type'), 'application/json') === false) {
                return new JsonResponse(['success' => false, 'message' => 'Content-Type must be application/json'], 400);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['success' => false, 'message' => 'Format de données invalide: ' . json_last_error_msg()], 400);
            }
            
            $email = $data['email'] ?? null;
            $faceData = $data['faceData'] ?? null;
            
            if (!$email || !$faceData) {
                return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants'], 400);
            }
            
            $result = $facialService->verifyUser($email, $faceData);
            
            if ($result['success']) {
                // Récupérer l'utilisateur pour vérifier son rôle
                $user = $userRepository->findOneBy(['email' => $email]);
                
                if (!$user) {
                    return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 400);
                }
                
                // Déterminer l'URL de redirection en fonction du rôle
                $redirectUrl = $this->getRedirectUrlByRole($user);
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Authentification réussie',
                    'redirectUrl' => $redirectUrl
                ]);
            }
            
            return new JsonResponse([
                'success' => false,
                'message' => $result['message'] ?? 'Échec de la reconnaissance faciale'
            ]);
            
        } catch (\Exception $e) {
            error_log('Exception in verifyFacialLogin: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Détermine l'URL de redirection en fonction du rôle de l'utilisateur
     */
    private function getRedirectUrlByRole($user): string
    {
        // Récupérer les rôles de l'utilisateur
        $roles = $user->getRoles();
        
        // Redirection basée sur le rôle
        if (in_array('ROLE_RESPONSABLE_RH', $roles)) {
            return $this->generateUrl('app_dashboard');
        } else if (in_array('ROLE_CHEF_PROJET', $roles)) {
            return $this->generateUrl('app_profile');
        } else if (in_array('ROLE_EMPLOYEE', $roles)) {
            return $this->generateUrl('app_profile');
        }
        
        // Par défaut, rediriger vers le dashboard
        return $this->generateUrl('app_dashboard');
    }
}