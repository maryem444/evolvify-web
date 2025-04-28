<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Projet;
use App\Entity\Tache;
use App\Entity\Conge;
use App\Entity\Absence;
use App\Entity\Abonnement;
use App\Entity\MoyenTransport;
use App\Entity\StatutProjet;
use App\Entity\StatutTache;
use App\Entity\CongeStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        EntityManagerInterface $entityManager
    ): Response {
        // Ensure user is fully authenticated and has admin role
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_RH');
        
        // Get current user
        $user = $this->getUser();
        
        // Get repositories
        $userRepository = $entityManager->getRepository(User::class);
        $projetRepository = $entityManager->getRepository(Projet::class);
        $tacheRepository = $entityManager->getRepository(Tache::class);
        $congeRepository = $entityManager->getRepository(Conge::class);
        $absenceRepository = $entityManager->getRepository(Absence::class);
        $abonnementRepository = $entityManager->getRepository(Abonnement::class);
        $transportRepository = $entityManager->getRepository(MoyenTransport::class);
        
        // Get counts for statistics cards
        $employeeCount = $userRepository->count([]);
        $projectCount = $projetRepository->count([]);
        $taskCount = $tacheRepository->count([]);
        $congeCount = $congeRepository->count([]);
        $absenceCount = $absenceRepository->count([]);
        $abonnementCount = $abonnementRepository->count([]);
        $transportCount = $transportRepository->count([]);

        // Get recent users (last 6)
        $recentEmployees = $userRepository->findBy(
            [], 
            ['id' => 'DESC'],
            6
        );

        // Get recent projects (last 5)
        $recentProjects = $projetRepository->findBy(
            [], 
            ['id_projet' => 'DESC'],
            5
        );

        // Get project status distribution - matching template keys
        $projectsByStatus = [
            'EN_COURS' => $projetRepository->count(['status' => StatutProjet::IN_PROGRESS]) ?? 0,
            'COMPLETE' => $projetRepository->count(['status' => StatutProjet::COMPLETED]) ?? 0,
        ];

        // Get task status distribution - matching template keys
        $tasksByStatus = [
            'A_FAIRE' => $tacheRepository->count(['status' => StatutTache::TO_DO]) ?? 0,
            'EN_COURS' => $tacheRepository->count(['status' => StatutTache::IN_PROGRESS]) ?? 0,
            'TERMINE' => $tacheRepository->count(['status' => StatutTache::DONE]) ?? 0,
        ];

        // Get leave status distribution - matching template keys
        $congesByStatus = [
            'APPROUVE' => $congeRepository->count(['statusString' => CongeStatus::ACCEPTE->value]) ?? 0,
            'EN_ATTENTE' => $congeRepository->count(['statusString' => CongeStatus::EN_COURS->value]) ?? 0,
            'REFUSE' => $congeRepository->count(['statusString' => CongeStatus::REFUSE->value]) ?? 0,
        ];

        // Get upcoming leaves (next 5)
        $today = new \DateTime();
        $upcomingConges = $congeRepository->createQueryBuilder('c')
            ->where('c.leaveStart >= :today')
            ->setParameter('today', $today)
            ->orderBy('c.leaveStart', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Get recent absences
        $recentAbsences = $absenceRepository->findBy(
            [], 
            ['id' => 'DESC'],
            5
        );

        // Get gender distribution data
        $maleCount = $userRepository->count(['gender' => 'HOMME']) ?? 0;
        $femaleCount = $userRepository->count(['gender' => 'FEMME']) ?? 0;
        $genderDistribution = [
            'HOMME' => $maleCount,
            'FEMME' => $femaleCount,
        ];

        // Get role distribution data
        $roleDistribution = [
            'RESPONSABLE_RH' => $userRepository->count(['role' => 'RESPONSABLE_RH']) ?? 0,
            'CHEF_PROJET' => $userRepository->count(['role' => 'CHEF_PROJET']) ?? 0,
            'EMPLOYEE' => $userRepository->count(['role' => 'EMPLOYEE']) ?? 0,
            'CONDIDAT' => $userRepository->count(['role' => 'CONDIDAT']) ?? 0,
        ];

        // Generate monthly data for charts (last 6 months)
        $chartLabels = [];
        $projectData = [];
        $taskData = [];
        $congeData = [];
        $absenceData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("first day of -$i month");
            $chartLabels[] = $date->format('M Y');
            
            $monthStart = clone $date;
            $monthEnd = clone $date;
            $monthEnd->modify('last day of this month');
            
            // Count projects created in this month
            $monthlyProjects = $projetRepository->createQueryBuilder('p')
                ->select('COUNT(p.id_projet)')
                ->where('p.dateCreation BETWEEN :start AND :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();
            $projectData[] = $monthlyProjects;
            
            // Count tasks created in this month
            $monthlyTasks = $tacheRepository->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.dateCreation BETWEEN :start AND :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();
            $taskData[] = $monthlyTasks;
            
            // Count leaves created in this month
            $monthlyConges = $congeRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.dateCreation BETWEEN :start AND :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();
            $congeData[] = $monthlyConges;
            
            // Count absences in this month
            $monthlyAbsences = $absenceRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.dateabsence BETWEEN :start AND :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()
                ->getSingleScalarResult();
            $absenceData[] = $monthlyAbsences;
        }
        
        // Create response with all data
        $response = $this->render('dashboard.html.twig', [
            'user' => $user,
            'employeeCount' => $employeeCount,
            'projectCount' => $projectCount,
            'taskCount' => $taskCount,
            'congeCount' => $congeCount,
            'absenceCount' => $absenceCount,
            'abonnementCount' => $abonnementCount,
            'transportCount' => $transportCount,
            'recentEmployees' => $recentEmployees,
            'recentProjects' => $recentProjects,
            'upcomingConges' => $upcomingConges,
            'recentAbsences' => $recentAbsences,
            'projectsByStatus' => $projectsByStatus,
            'tasksByStatus' => $tasksByStatus, 
            'congesByStatus' => $congesByStatus,
            'genderDistribution' => $genderDistribution,
            'roleDistribution' => $roleDistribution,
            'chartLabels' => $chartLabels,
            'projectData' => $projectData,
            'taskData' => $taskData,
            'congeData' => $congeData,
            'absenceData' => $absenceData,
        ]);
        
        // Set cache headers to prevent back-button issues after logout
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        
        return $response;
    }
}