<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FacialSaveController extends AbstractController
{
// Dans FacialSaveController.php, ajoutez ces logs supplémentaires
#[Route('/save-facial-data', name: 'app_save_facial_data', methods: ['POST'])]
public function saveFacialData(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    
    /** @var User $user */
    $user = $this->getUser();
    
    if (!$user) {
        $logger->error('Tentative d\'accès non authentifiée');
        return $this->json(['success' => false, 'message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
    }
    
    $logger->info('Tentative de sauvegarde des données faciales pour l\'utilisateur ' . $user->getId());
    $logger->debug('Contenu de la requête: ' . $request->getContent());
    
    try {
        // Get data from request
        $data = json_decode($request->getContent(), true);
        
        $logger->debug('Données décodées: ' . json_encode($data));
        
        if (null === $data) {
            $logger->error('Données JSON invalides reçues');
            return $this->json(['success' => false, 'message' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }
        
        if (!isset($data['faceDescriptor']) || !isset($data['imageData'])) {
            $logger->error('Champs de données requis manquants: ' . json_encode(array_keys($data)));
            return $this->json(['success' => false, 'message' => 'Missing required data'], Response::HTTP_BAD_REQUEST);
        }
        
        // Process image data
        $imageData = $data['imageData'];
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $decodedImage = base64_decode($imageData);
        
        if ($decodedImage === false) {
            $logger->error('Échec du décodage de l\'image base64');
            return $this->json(['success' => false, 'message' => 'Invalid image data'], Response::HTTP_BAD_REQUEST);
        }
        
        // Generate unique filename
        $filename = 'face_' . $user->getId() . '_' . uniqid() . '.jpg';
        
        // Create upload directory if it doesn't exist
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/facial';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Save image to file
        $filePath = $uploadDir . '/' . $filename;
        if (file_put_contents($filePath, $decodedImage) === false) {
            $logger->error('Échec de la sauvegarde du fichier image dans: ' . $filePath);
            return $this->json(['success' => false, 'message' => 'Failed to save image file'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        // Prepare facial data for storage
        $facialData = [
            'descriptor' => $data['faceDescriptor'],
            'imageFilename' => $filename
        ];
        
        // Update user entity
        $user->setFacialData(json_encode($facialData));
        $user->setFaceImageFilename($filename);
        $user->setFacialAuthEnabled(true);
        
        // Debug log
        $logger->debug('Avant persist - Données faciales: ' . $user->getFacialData());
        
        // Save to database
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Debug log après flush
        $logger->debug('Après flush - Données faciales: ' . $user->getFacialData());
        
        $logger->info('Données faciales sauvegardées avec succès pour l\'utilisateur: ' . $user->getId());
        return $this->json(['success' => true, 'message' => 'Facial data saved successfully']);
        
    } catch (\Exception $e) {
        $logger->error('Erreur lors de la sauvegarde des données faciales: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return $this->json(
            ['success' => false, 'message' => 'Error saving facial data: ' . $e->getMessage()],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}

    #[Route('/disable-facial-auth', name: 'app_disable_facial_auth', methods: ['POST'])]
    public function disableFacialAuth(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        try {
            // Delete image file if exists
            if ($user->getFaceImageFilename()) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/facial';
                $imagePath = $uploadDir . '/' . $user->getFaceImageFilename();
                
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                    $logger->info('Deleted facial image: ' . $imagePath);
                }
            }
            
            // Update user entity
            $user->setFacialData(null);
            $user->setFaceImageFilename(null);
            $user->setFacialAuthEnabled(false);
            
            // Save to database
            $entityManager->persist($user);
            $entityManager->flush();
            
            $logger->info('Facial authentication disabled for user: ' . $user->getId());
            return $this->json(['success' => true, 'message' => 'Facial authentication disabled']);
            
        } catch (\Exception $e) {
            $logger->error('Error disabling facial auth: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->json(
                ['success' => false, 'message' => 'Error disabling facial authentication: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}