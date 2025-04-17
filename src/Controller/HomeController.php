<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Get the authenticated user from session (handled by Symfony)
        $user = $this->getUser();
        
        // If no user is authenticated, redirect to login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get the user's role - use getRoles() method from UserInterface
        // (Fixed from direct property access which is not secure)
        $roles = $user->getRoles();
        
        // Apply role-based redirection
        if (in_array('ROLE_RESPONSABLE_RH', $roles)) {
            return $this->redirectToRoute('app_dashboard');
        } elseif (in_array('ROLE_CHEF_PROJET', $roles)) {
            return $this->redirectToRoute('app_dashboard');
        } elseif (in_array('ROLE_EMPLOYEE', $roles) || in_array('ROLE_CONDIDAT', $roles)) {
            return $this->redirectToRoute('app_profile');
        } else {
            // Default fallback if role doesn't match any specific route
            throw new AccessDeniedException('You do not have permission to access this page.');
        }
    }
}