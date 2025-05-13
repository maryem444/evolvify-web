<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_RH', null, 'Accès interdit: vous n\'avez pas les droits nécessaires.');

        $response = $this->render('dashboard/index.html.twig', [
            'employeeCount' => 42,
            'projectCount' => 10,
            'taskCount' => 23,
            'congeCount' => 7,
            'absenceCount' => 5,
            'transportCount' => 4,
            'recruitmentCount' => 3,
            'genderDistribution' => ['Homme' => 25, 'Femme' => 17],
            'roleDistribution' => ['RH' => 5, 'Chef Projet' => 8, 'Employé' => 29],
            'recentUsers' => [
                ['name' => 'Amine Saidi', 'role' => 'Employé'],
                ['name' => 'Fatma Ben Ali', 'role' => 'Chef Projet'],
                ['name' => 'Yassine Hammami', 'role' => 'RH'],
            ],
        ]);
        
        // Ajouter les en-têtes de cache pour empêcher la navigation retour
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }
}