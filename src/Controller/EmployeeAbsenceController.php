<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\AbsenceStatus;
use App\Repository\AbsenceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/employees/my-attendance')]
class EmployeeAbsenceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AbsenceRepository $absenceRepository;
    private UserRepository $userRepository;
    private int $employeeId = 114; // Static employee ID

    public function __construct(
        EntityManagerInterface $entityManager,
        AbsenceRepository $absenceRepository,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->absenceRepository = $absenceRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'app_employee_my_attendance')]
    public function index(Request $request): Response
    {
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
            ->setParameter('employeeId', $this->employeeId)
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
            'employeeId' => $this->employeeId,
            'modifiableDays' => $modifiableDays
        ]);
    }

    #[Route('/mark', name: 'app_employee_mark_attendance', methods: ['POST'])]
    public function markAttendance(Request $request): JsonResponse
    {
        try {
            // For debugging purposes
            $content = $request->getContent();
            $contentType = $request->getContentType();
            
            // Ensure we have content
            if (empty($content)) {
                return new JsonResponse(['success' => false, 'message' => 'No content provided'], 400);
            }
            
            // Parse JSON data
            $data = json_decode($content, true);
            
            // Check if JSON was decoded properly
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON provided: ' . json_last_error_msg());
            }
            
            if (!isset($data['date'], $data['status'])) {
                return new JsonResponse(['success' => false, 'message' => 'Missing required parameters'], 400);
            }
            
            $date = new \DateTime($data['date']);
            $status = $data['status'];
            
            // Set time to beginning of day to ensure consistent date comparison
            $date->setTime(0, 0, 0);
            
            // Verify the date is not in the future
            $today = new \DateTime();
            $today->setTime(23, 59, 59);
            
            if ($date > $today) {
                return new JsonResponse(['success' => false, 'message' => 'Cannot mark attendance for future dates'], 400);
            }
            
            // Verify it's not a weekend
            $dayOfWeek = (int)$date->format('N');
            if ($dayOfWeek >= 6) {
                return new JsonResponse(['success' => false, 'message' => 'Cannot mark attendance for weekends'], 400);
            }
            
            // Check if there's already an absence record for this employee on this date
            $absence = $this->absenceRepository->findOneBy([
                'idEmploye' => $this->employeeId,
                'date' => $date,
            ]);
            
            if (!$absence) {
                // Create a new absence record if none exists
                $absence = new Absence();
                $absence->setIdEmploye($this->employeeId);
                $absence->setDate($date);
            }
            
            // Set the status
            $absenceStatus = AbsenceStatus::from($status);
            $absence->setStatus($absenceStatus);
            
            // Save changes
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
                ->setParameter('employeeId', $this->employeeId)
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
            'employeeId' => $this->employeeId
        ]);
    }
}