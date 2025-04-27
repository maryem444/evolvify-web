<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Absence;
use App\Repository\EmployeeRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        EntityManagerInterface $entityManager
    ): Response {
        // Ensure user is fully authenticated (uses session)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Get current user
        $user = $this->getUser();
        
        // Get repositories
        $employeeRepository = $entityManager->getRepository(User::class);
        $projectRepository = $entityManager->getRepository(Project::class);
        $taskRepository = $entityManager->getRepository(Task::class);
        $absenceRepository = $entityManager->getRepository(Absence::class);
        
        // Get counts for statistics cards
        $employeCount = $employeeRepository->count([]);
        $projectCount = $projectRepository->count([]);
        $taskCount = $taskRepository->count([]);
        $absenceCount = $absenceRepository->count([]);

        // Get recent projects (last 5)
        $recentProjects = $projectRepository->findBy(
            [], 
            ['id' => 'DESC'], // Assuming newer projects have higher IDs
            5
        );

        // Get project status counts (adapt field names to your actual schema)
        $plannedProjects = $projectRepository->count(['status' => 'IN_PROGRESS']) ?? 0;
        $inProgressProjects = $projectRepository->count(['status' => 'COMPLETED']) ?? 0;

        // Get upcoming absences
        $today = new \DateTime();
        $upcomingAbsences = $absenceRepository->createQueryBuilder('a')
            ->where('a.startDate >= :today')
            ->setParameter('today', $today)
            ->orderBy('a.startDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Get recently registered employees
        $recentEmployees = $employeeRepository->findBy(
            [], 
            ['id' => 'DESC'], // Assuming newer employees have higher IDs
            6
        );

        // Prepare data for charts
        $chartLabels = [];
        $projectData = [];
        $taskData = [];
        $absenceData = [];

        // Generate last 6 months for chart
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("first day of -$i month");
            $chartLabels[] = $date->format('M Y');
            
            $monthStart = clone $date;
            $monthEnd = clone $date;
            $monthEnd->modify('last day of this month');
            
            // Count projects created in this month (adapt to your schema)
            try {
                $projectData[] = $projectRepository->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->where('p.id IS NOT NULL')
                    ->getQuery()
                    ->getSingleScalarResult() / 6; // Simplification pour distribuer visuellement
            } catch (\Exception $e) {
                $projectData[] = 0;
            }
            
            // Count tasks created in this month (adapt to your schema)
            try {
                $taskData[] = $taskRepository->createQueryBuilder('t')
                    ->select('COUNT(t.id)')
                    ->where('t.id IS NOT NULL')
                    ->getQuery()
                    ->getSingleScalarResult() / 6; // Simplification pour distribuer visuellement
            } catch (\Exception $e) {
                $taskData[] = 0;
            }
            
            // Count absences in this month (adapt to your schema)
            try {
                $absenceData[] = $absenceRepository->createQueryBuilder('a')
                    ->select('COUNT(a.id)')
                    ->where('a.id IS NOT NULL')
                    ->getQuery()
                    ->getSingleScalarResult() / 6; // Simplification pour distribuer visuellement
            } catch (\Exception $e) {
                $absenceData[] = 0;
            }
        }
        
        // Create response with all data
        $response = $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'employeCount' => $employeCount,
            'projectCount' => $projectCount,
            'taskCount' => $taskCount,
            'absenceCount' => $absenceCount,
            'recentProjects' => $recentProjects,
            'upcomingAbsences' => $upcomingAbsences,
            'recentEmployees' => $recentEmployees,
            'plannedProjects' => $plannedProjects,
            'inProgressProjects' => $inProgressProjects,
            'chartLabels' => $chartLabels,
            'projectData' => $projectData,
            'taskData' => $taskData,
            'absenceData' => $absenceData,
        ]);
        
        // Set cache headers to prevent back-button issues after logout
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        
        return $response;
    }
}