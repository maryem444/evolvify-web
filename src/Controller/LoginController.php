<?php
// src/Controller/SecurityController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect to home page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        $response = $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
        
        // No caching for login page
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        
        return $response;
    }
    
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method will be intercepted by the logout key in security.yaml
        // No code needed here, but the route must exist
        
        // Note: To ensure proper logout, you need to configure security.yaml with:
        // logout:
        //     path: app_logout
        //     target: app_login
        //     invalidate_session: true
        //     clear_site_data: ["*"]  # Clears all browser storage
    }
}