<?php
// src/Controller/Api/SessionController.php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SessionController extends AbstractController
{
    /**
     * API endpoint to verify if the user session is still valid
     * Used to prevent the back button issue after logout 
     */
    #[Route('/api/verify-session', name: 'api_verify_session')]
    public function verifySession(): JsonResponse
    {
        // Check if user is logged in
        if (!$this->getUser()) {
            return new JsonResponse(['authenticated' => false], 401);
        }
        
        // User is authenticated
        return new JsonResponse([
            'authenticated' => true,
            'username' => $this->getUser()->getUserIdentifier()
        ]);
    }
}