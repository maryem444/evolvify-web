<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Get EntityManager to access all repositories through UserRepository
        $entityManager = $this->userRepository->getEntityManager();
        
        // 1. Récupération des utilisateurs par rôle
        $usersByRole = $this->userRepository->countUsersByRole();
        
        // 2. Récupération des projets par statut
        $projetRepository = $entityManager->getRepository('App:Projet');
        $projectsByStatus = $projetRepository->createQueryBuilder('p')
            ->select('p.status as status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();
        
        // 3. Récupération des tâches par statut
        $tacheRepository = $entityManager->getRepository('App:Tache');
        $tasksByStatus = $tacheRepository->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();
        
        // 4. Récupération des congés par statut
        $congeRepository = $entityManager->getRepository('App:Conge');
        $leavesByStatus = $congeRepository->createQueryBuilder('c')
            ->select('c.status as status, COUNT(c.idConge) as count')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();
        
        // Si le champ s'appelle statusString et non status
        if (empty($leavesByStatus)) {
            $leavesByStatus = $congeRepository->createQueryBuilder('c')
                ->select('c.statusString as status, COUNT(c.idConge) as count')
                ->groupBy('c.statusString')
                ->getQuery()
                ->getResult();
        }
        
        // 5. Récupération des projets récents
        $recentProjects = $projetRepository->findBy(
            [],
            ['starterAt' => 'DESC'],
            5
        );
        
        // 6. Récupération des tâches récentes
        $recentTasks = $tacheRepository->findBy(
            [],
            ['id' => 'DESC'], // Remplacez par le champ de date approprié si disponible
            5
        );
        
        // Ajouter des noms complets pour les employés des tâches
        foreach ($recentTasks as $task) {
            $employe = $task->getEmploye();
            if ($employe) {
                $task->employeFullName = $employe->getFirstname() . ' ' . $employe->getLastname();
            } else {
                $task->employeFullName = 'Non assigné';
            }
        }
        
        // 7. Récupération des soldes de congés
        $leaveBalances = $this->userRepository->getLeaveBalances();
        
        return $this->render('dashboard.html.twig', [
            'usersByRole' => $usersByRole,
            'projectsByStatus' => $projectsByStatus,
            'tasksByStatus' => $tasksByStatus,
            'leavesByStatus' => $leavesByStatus,
            'recentProjects' => $recentProjects,
            'recentTasks' => $recentTasks,
            'leaveBalances' => $leaveBalances,
            'userCount' => $this->userRepository->countTotal()
        ]);
    }
}