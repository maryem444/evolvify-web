<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FacialSaveController extends AbstractController
{
    #[Route('/account/save-facial-data', name: 'app_save_facial_data', methods: ['POST'])]
    public function saveFacialData(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('save_facial_data', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }
        
        // Get current user
        $user = $this->getUser();
        
        try {
            // Get data from request
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                return $this->json(['success' => false, 'message' => 'Invalid JSON data'], 400);
            }
            
            if (!isset($data['faceDescriptor']) || !isset($data['imageData'])) {
                return $this->json(['success' => false, 'message' => 'Missing required data'], 400);
            }
            
            // Save face descriptor
            $user->setFacialData(json_encode($data['faceDescriptor']));
            $user->setFacialAuthEnabled(true);
            
            // Save reference image
            $imageData = $data['imageData'];
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageData = base64_decode($imageData);
            
            // Generate unique filename
            $filename = 'face_' . $user->getId() . '_' . uniqid() . '.jpg';
            
            // Create upload directory if it doesn't exist
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/facial';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Save image to directory
            file_put_contents($uploadDir . '/' . $filename, $imageData);
            
            // Save filename reference
            $facialData = [
                'descriptor' => $data['faceDescriptor'],
                'imageFilename' => $filename
            ];
            $user->setFacialData(json_encode($facialData));
            $user->setFaceImageFilename($filename);
            
            // Save to database
            $entityManager->persist($user);
            $entityManager->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            // Log the full exception for debugging
            $logger->error('Erreur lors de l\'enregistrement facial: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/account/disable-facial-auth', name: 'app_disable_facial_auth', methods: ['POST'])]
    public function disableFacialAuth(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('disable_facial_auth', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }
        
        // Get current user
        $user = $this->getUser();
        
        try {
            // Disable facial auth
            $user->setFacialAuthEnabled(false);
            
            // Clear facial data
            $user->setFacialData(null);
            
            // Delete image file if exists
            if ($user->getFaceImageFilename()) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/facial';
                $imagePath = $uploadDir . '/' . $user->getFaceImageFilename();
                
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                $user->setFaceImageFilename(null);
            }
            
            // Save to database
            $entityManager->persist($user);
            $entityManager->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la désactivation faciale: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->json(['success' => false, 'message' => 'Erreur lors de la désactivation: ' . $e->getMessage()], 500);
        }
    }
}