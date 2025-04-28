<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserBiometricData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class FaceIdController extends AbstractController
{
    private $entityManager;
    private $security;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, 
        Security $security,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->logger = $logger;
    }

    #[Route('/profile/setup-face-id', name: 'app_setup_face_id', methods: ['POST'])]
    public function setupFaceId(Request $request): Response
    {
        // Get the current user
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour configurer Face ID.');
            return $this->redirectToRoute('app_login');
        }

        // Ensure we have the correct User type
        if (!$user instanceof User) {
            $userEmail = $user->getUserIdentifier();
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $userEmail]);
            
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé.');
                return $this->redirectToRoute('app_profile');
            }
        }

        // Get the facial data from the form - check if it's in request body for AJAX requests
        $content = json_decode($request->getContent(), true);
        $faceData = $request->request->get('faceData');
        
        // If not in standard form data, check JSON body
        if (!$faceData && isset($content['faceData'])) {
            $faceData = $content['faceData'];
        }
        
        if (!$faceData) {
            $this->addFlash('error', 'Aucune donnée biométrique n\'a été fournie.');
            return $this->redirectToRoute('app_profile');
        }
        
        // Validate face data - make sure it's large enough to be a real face image
        if (strlen($faceData) < 1000) {
            $this->addFlash('error', 'Les données biométriques sont invalides ou incomplètes.');
            return $this->redirectToRoute('app_profile');
        }

        // Check if face data contains an actual face using image validation
        $validFaceData = $this->validateFaceData($faceData);
        if (!$validFaceData) {
            $this->addFlash('error', 'Aucun visage détecté dans l\'image. Veuillez réessayer.');
            return $this->redirectToRoute('app_profile');
        }

        // Process the raw face data
        $processedFaceData = $this->processFaceData($faceData);

        // Check if the user already has biometric data
        $repository = $this->entityManager->getRepository(UserBiometricData::class);
        $biometricData = $repository->findOneBy(['user' => $user]);

        if (!$biometricData) {
            // Create new biometric record
            $biometricData = new UserBiometricData();
            $biometricData->setUser($user);
            $biometricData->setCreatedAt(new \DateTime());
        }

        // Update biometric data
        $biometricData->setFaceModelData($processedFaceData);
        $biometricData->setUpdatedAt(new \DateTime());
        $biometricData->setEnabled(true);

        // Save to database - make sure to persist both objects
        $this->entityManager->persist($biometricData);
        
        // Update user record - use setter methods on the User entity
        $user->setFacialAuthEnabled(true);
        $user->setHasFacialRecognition(true);
        $user->setLastFacialAuth(new \DateTime());
        $this->entityManager->persist($user);
        
        // Flush changes
        $this->entityManager->flush();
        
        $this->logger->info('Face ID setup completed for user: ' . $user->getEmail());

        // Return a JSON response for AJAX requests
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => 'Face ID configuré avec succès! Vous pouvez maintenant vous connecter en utilisant la reconnaissance faciale.'
            ]);
        }

        $this->addFlash('success', 'Face ID configuré avec succès! Vous pouvez maintenant vous connecter en utilisant la reconnaissance faciale.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/deactivate-face-id', name: 'app_deactivate_face_id', methods: ['POST'])]
    public function deactivateFaceId(): Response
    {
        // Get the current user
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour désactiver Face ID.');
            return $this->redirectToRoute('app_login');
        }

        // Ensure we have the correct User type
        if (!$user instanceof User) {
            $userEmail = $user->getUserIdentifier();
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $userEmail]);
            
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé.');
                return $this->redirectToRoute('app_profile');
            }
        }

        // Find the user's biometric data
        $repository = $this->entityManager->getRepository(UserBiometricData::class);
        $biometricData = $repository->findOneBy(['user' => $user]);

        if ($biometricData) {
            // Disable biometric data but keep the record
            $biometricData->setEnabled(false);
            $biometricData->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($biometricData);
        }

        // Update user record
        $user->setFacialAuthEnabled(false);
        $user->setHasFacialRecognition(false);
        $this->entityManager->persist($user);
        
        $this->entityManager->flush();
        
        $this->logger->info('Face ID deactivated for user: ' . $user->getEmail());

        $this->addFlash('success', 'Face ID désactivé avec succès.');
        return $this->redirectToRoute('app_profile');
    }

    /**
     * Validate that the face data contains an actual face
     * This is a simple implementation - in production, use a facial detection library
     */
    private function validateFaceData(string $faceData): bool
    {
        // Basic validation - ensure the image is of reasonable size
        if (strlen($faceData) < 1000) {
            $this->logger->warning('Face data too small to be valid');
            return false;
        }
        
        // Check if it's a data URL
        if (strpos($faceData, 'data:image/jpeg;base64,') === 0) {
            $faceData = substr($faceData, strlen('data:image/jpeg;base64,'));
        }
        
        // Validate it's valid base64
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $faceData)) {
            $this->logger->error('Invalid base64 data received');
            return false;
        }
        
        // In a production environment, you would:
        // 1. Decode the base64 image
        // 2. Use a face detection library to verify a face is present
        // 3. Verify the quality of the face image (lighting, clarity, etc.)
        
        // For this implementation, we'll just check if the data is reasonably sized
        return strlen($faceData) >= 1000;
    }

    /**
     * Process and prepare facial data for storage
     * In a real app, you would use a facial recognition library here
     */
    private function processFaceData(string $faceData): string
    {
        // Remove the data URL prefix if present
        if (strpos($faceData, 'data:image/jpeg;base64,') === 0) {
            $faceData = substr($faceData, strlen('data:image/jpeg;base64,'));
        }
        
        // In a real implementation, you would:
        // 1. Decode the base64 image
        // 2. Use a facial recognition library to extract features
        // 3. Create a model or feature vector
        // 4. Encrypt the sensitive biometric data before storage
        
        // For this example, we'll just return the processed base64 data
        return $faceData;
    }
}