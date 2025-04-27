<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\AbsenceRepository;
use App\Repository\CongeRepository;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private CongeRepository $congeRepository;
    private ProjetRepository $projetRepository;
    private TacheRepository $tacheRepository;
    private AbsenceRepository $absenceRepository;
    private AbonnementRepository $abonnementRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CongeRepository $congeRepository,
        ProjetRepository $projetRepository,
        TacheRepository $tacheRepository,
        AbsenceRepository $absenceRepository,
        AbonnementRepository $abonnementRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->congeRepository = $congeRepository;
        $this->projetRepository = $projetRepository;
        $this->tacheRepository = $tacheRepository;
        $this->absenceRepository = $absenceRepository;
        $this->abonnementRepository = $abonnementRepository;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        // Ensure user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Get current user
        /** @var User $user */
        $user = $this->getUser();
        
        // Ensure user has ROLE_RESPONSABLE_RH
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_RH');
        
        // Get dashboard statistics
        $currentDate = new \DateTime();
        
        // Users by role
        // Requête pour les utilisateurs par rôle
        $usersByRole = $this->userRepository->createQueryBuilder('u')
            ->select('u.role, COUNT(u.id) as count')
            ->groupBy('u.role')
            ->getQuery()
            ->getResult();

        // Absences this month
        $absencesThisMonth = $this->absenceRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as count, a.status')
            ->where('MONTH(a.date) = :month')
            ->andWhere('YEAR(a.date) = :year')
            ->setParameter('month', $currentDate->format('m'))
            ->setParameter('year', $currentDate->format('Y'))
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        // Projects by status
// Modification de la requête pour les projets
        $projectsByStatus = $this->projetRepository->createQueryBuilder('p')
            ->select('p.status as status, COUNT(p) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();
        // Tasks by status
        $tasksByStatus = $this->tacheRepository->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id_tache) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        // Leaves by status
// Modification de la requête pour les congés
        $leavesByStatus = $this->congeRepository->createQueryBuilder('c')
            ->select('c.statusString as status, COUNT(c) as count')
            ->groupBy('c.statusString')
            ->getQuery()
            ->getResult();
        // Subscriptions by status
        $subscriptionsByStatus = $this->abonnementRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id_Ab) as count')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();

        // Employees by gender
        $employeesByGender = $this->userRepository->createQueryBuilder('u')
            ->select('u.gender, COUNT(u.id) as count')
            ->where('u.gender IS NOT NULL')
            ->groupBy('u.gender')
            ->getQuery()
            ->getResult();

        // Recent hires
        $recentHires = $this->userRepository->createQueryBuilder('u')
            ->where('u.joiningDate IS NOT NULL')
            ->orderBy('u.joiningDate', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Recent projects
        $recentProjects = $this->projetRepository->createQueryBuilder('p')
            ->orderBy('p.starterAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        // Recent leaves
        $recentLeaves = $this->congeRepository->createQueryBuilder('c')
            ->orderBy('c.leaveStart', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Recent tasks
        $recentTasks = $this->tacheRepository->createQueryBuilder('t')
            ->orderBy('t.created_at', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Get leaves by month (last 12 months)
        $startDate = (new \DateTime())->modify('-11 months')->modify('first day of this month');
        $endDate = new \DateTime();
        
        $leavesByMonth = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $month = $currentDate->format('Y-m');
            $monthName = $currentDate->format('M Y');
            
            $leaveCount = $this->congeRepository->createQueryBuilder('c')
                ->select('COUNT(c.idConge)')
                ->where('YEAR(c.leaveStart) = :year')
                ->andWhere('MONTH(c.leaveStart) = :month')
                ->setParameter('year', $currentDate->format('Y'))
                ->setParameter('month', $currentDate->format('m'))
                ->getQuery()
                ->getSingleScalarResult();
            
            $leavesByMonth[] = [
                'month' => $monthName,
                'count' => (int)$leaveCount
            ];
            
            $currentDate->modify('+1 month');
        }

        // Get leave balances
        $leaveBalances = $this->userRepository->createQueryBuilder('u')
            ->select('u.id, u.firstname, u.lastname, u.conge_restant')
            ->where('u.conge_restant IS NOT NULL')
            ->orderBy('u.conge_restant', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Get telecommuting balances
        $ttBalances = $this->userRepository->createQueryBuilder('u')
            ->select('u.id, u.firstname, u.lastname, u.tt_restants')
            ->where('u.tt_restants IS NOT NULL')
            ->orderBy('u.tt_restants', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

            
        // Render the dashboard template
        $response = $this->render('dashboard.html.twig', [
            'usersByRole' => $usersByRole,
            'absencesThisMonth' => $absencesThisMonth,
            'projectsByStatus' => $projectsByStatus,
            'tasksByStatus' => $tasksByStatus,
            'leavesByStatus' => $leavesByStatus,
            'subscriptionsByStatus' => $subscriptionsByStatus,
            'employeesByGender' => $employeesByGender,
            'recentHires' => $recentHires,
            'recentProjects' => $recentProjects,
            'recentLeaves' => $recentLeaves,
            'recentTasks' => $recentTasks,
            'leavesByMonth' => $leavesByMonth,
            'leaveBalances' => $leaveBalances,
            'ttBalances' => $ttBalances,
        ]);
        
        // Set cache headers to prevent caching
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        
        return $response;
    }

    #[Route('/test-conge', name: 'app_test_conge')]
    public function testConge(): Response
    {
        $leavesByStatus = $this->congeRepository->createQueryBuilder('c')
            ->select('c.statusString as status, COUNT(c) as count')
            ->groupBy('c.statusString')
            ->getQuery()
            ->getResult();
        
        return new Response('<pre>' . print_r($leavesByStatus, true) . '</pre>');
    }
}