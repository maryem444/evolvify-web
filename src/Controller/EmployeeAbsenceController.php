<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\AbsenceStatus;
use App\Entity\User;
use App\Repository\AbsenceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/employees/my-attendance')]
class EmployeeAbsenceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AbsenceRepository $absenceRepository;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AbsenceRepository $absenceRepository,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->absenceRepository = $absenceRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get the current user or throw an exception if not authenticated
     */
    private function getCurrentUser(): User
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        return $user;
    }

    #[Route('/', name: 'app_employee_my_attendance')]
    public function index(Request $request): Response
    {
        $user = $this->getCurrentUser();
        $employeeId = $user->getId();
        
        // Get month and year from request, default to current month/year
        $month = $request->query->get('month', date('F'));
        $year = (int)$request->query->get('year', date('Y'));
        
        // Convert month name to number
        $monthNumber = date('m', strtotime("$month 1, $year"));
        
        // Calculate days in month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$monthNumber, $year);
        
        // Generate array of days for the month (1-31)
        $days = range(1, $daysInMonth);
        
        // Prepare attendance data structure
        $attendanceData = [];
        
        // Get first day of the week for this month
        $firstDayOfMonth = date('N', strtotime("$year-$monthNumber-01"));
        
        // Get all absences for the employee in the specified month
        $startDate = new \DateTime("$year-$monthNumber-01");
        $endDate = new \DateTime("$year-$monthNumber-$daysInMonth");
        $endDate->setTime(23, 59, 59);
        
        $absences = $this->absenceRepository->createQueryBuilder('a')
            ->where('a.date BETWEEN :startDate AND :endDate')
            ->andWhere('a.idEmploye = :employeeId')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('employeeId', $employeeId)
            ->getQuery()
            ->getResult();
        
        // Populate attendance data with actual statuses
        foreach ($absences as $absence) {
            $day = (int)$absence->getDate()->format('j'); // Day without leading zeros
            $attendanceData[$day] = $absence;
        }
        
        // Get all possible status options
        $statusOptions = AbsenceStatus::cases();
        
        // Check if it's today
        $today = (int)date('j');
        $currentMonth = date('F');
        $currentYear = (int)date('Y');
        $isCurrentMonth = ($month === $currentMonth && $year === $currentYear);
        
        // Calculate modifiable days
        $modifiableDays = [];
        $currentDate = new \DateTime();
        
        // Check if the month/year is in the past or current
        $isCurrentOrPastMonth = 
            strtotime("$year-$monthNumber-01") <= strtotime($currentDate->format('Y-m-01'));
        
        if ($isCurrentOrPastMonth) {
            // For past months, all non-weekend days are modifiable
            // For current month, only days up to today are modifiable
            $lastDay = $isCurrentMonth ? $today : $daysInMonth;
            
            for ($i = 1; $i <= $lastDay; $i++) {
                $checkDate = \DateTime::createFromFormat('Y-m-d', "$year-$monthNumber-$i");
                if ($checkDate) {
                    $dayOfWeek = (int)$checkDate->format('N');
                    if ($dayOfWeek < 6) { // Not weekend (1-5 are weekdays)
                        $modifiableDays[] = $i;
                    }
                }
            }
        }
        
        return $this->render('absence/indexEmp.html.twig', [
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'attendanceData' => $attendanceData,
            'statusOptions' => $statusOptions,
            'today' => $today,
            'isCurrentMonth' => $isCurrentMonth,
            'employeeId' => $employeeId,
            'modifiableDays' => $modifiableDays
        ]);
    }

    #[Route('/mark', name: 'app_employee_mark_attendance', methods: ['POST', 'GET'])]
    public function markAttendance(Request $request): JsonResponse
    {
        try {
            $user = $this->getCurrentUser();
            $employeeId = $user->getId();
            
            // For GET requests, check query parameters
            if ($request->isMethod('GET')) {
                $date = $request->query->get('date');
                $status = $request->query->get('status');
                
                // If navigating directly with no parameters, redirect to attendance page
                if (!$date || !$status) {
                    // Return a proper message for direct URL access
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Cette URL est réservée aux appels API. Veuillez utiliser l\'interface utilisateur pour marquer votre présence.'
                    ], 400);
                }
            } else {
                // For POST requests
                $content = $request->getContent();
                $data = json_decode($content, true);
                
                if ($data === null) {
                    // Try to get from request parameters
                    $date = $request->request->get('date');
                    $status = $request->request->get('status');
                } else {
                    $date = $data['date'] ?? null;
                    $status = $data['status'] ?? null;
                }
            }
            
            // Validate parameters
            if (!isset($date) || !isset($status)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Les paramètres date et status sont requis'
                ], 400);
            }
            
            // Rest of your code remains the same...
            $date = new \DateTime($date);
            $status = $status;
            
            // Set time to beginning of day
            $date->setTime(0, 0, 0);
            
            // Check if future date
            $today = new \DateTime();
            $today->setTime(23, 59, 59);
            
            if ($date > $today) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Impossible de marquer la présence pour une date future'
                ], 400);
            }
            
            // Check if weekend
            $dayOfWeek = (int)$date->format('N');
            if ($dayOfWeek >= 6) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Impossible de marquer la présence pour les weekends'
                ], 400);
            }
            
            // Get or create absence record
            $absence = $this->absenceRepository->findOneBy([
                'idEmploye' => $employeeId,
                'date' => $date,
            ]);
            
            if (!$absence) {
                $absence = new Absence();
                $absence->setIdEmploye($employeeId);
                $absence->setDate($date);
            }
            
            // Set status
            $absenceStatus = AbsenceStatus::from($status);
            $absence->setStatus($absenceStatus);
            
            // Save
            $this->entityManager->persist($absence);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Présence marquée avec succès pour le ' . $date->format('d/m/Y')
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    #[Route('/history', name: 'app_employee_attendance_history')]
    public function history(Request $request): Response
    {
        $user = $this->getCurrentUser();
        $employeeId = $user->getId();
        
        $year = (int)$request->query->get('year', date('Y'));
        
        $absencesByMonth = [];
        
        // For each month of the year
        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTime("$year-$month-01");
            $endDate = new \DateTime($startDate->format('Y-m-t'));
            $endDate->setTime(23, 59, 59);

            $absences = $this->absenceRepository->createQueryBuilder('a')
                ->where('a.date BETWEEN :startDate AND :endDate')
                ->andWhere('a.idEmploye = :employeeId')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('employeeId', $employeeId)
                ->getQuery()
                ->getResult();
            
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            
            $absencesByMonth[$monthName] = [
                'total' => count($absences),
                'present' => 0,
                'absent' => 0,
                'leave' => 0,
                'other' => 0,
            ];
            
            foreach ($absences as $absence) {
                switch ($absence->getStatus()) {
                    case AbsenceStatus::PRESENT:
                        $absencesByMonth[$monthName]['present']++;
                        break;
                    case AbsenceStatus::ABSENT:
                        $absencesByMonth[$monthName]['absent']++;
                        break;
                    case AbsenceStatus::EN_CONGE:
                        $absencesByMonth[$monthName]['leave']++;
                        break;
                    default:
                        $absencesByMonth[$monthName]['other']++;
                }
            }
        }
        
        return $this->render('absence/EmpHistory.html.twig', [
            'year' => $year,
            'absencesByMonth' => $absencesByMonth,
            'employeeId' => $employeeId
        ]);
    }
}