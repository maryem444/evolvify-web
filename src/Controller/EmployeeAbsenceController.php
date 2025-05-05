<?php

namespace App\Controller;

use App\Entity\Absence;
use App\Entity\AbsenceStatus;
use App\Entity\User;
use App\Repository\AbsenceRepository;
use App\Repository\UserRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twilio\Rest\Client; 

#[Route('/employees/my-attendance')]
class EmployeeAbsenceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AbsenceRepository $absenceRepository;
    private UserRepository $userRepository;
    private PdfService $pdfService;

    public function __construct(
        EntityManagerInterface $entityManager,
        AbsenceRepository $absenceRepository,
        UserRepository $userRepository,
        PdfService $pdfService
    ) {
        $this->entityManager = $entityManager;
        $this->absenceRepository = $absenceRepository;
        $this->userRepository = $userRepository;
        $this->pdfService = $pdfService;
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
            
            $dateObject = new \DateTime($date);
            
            // Set time to beginning of day
            $dateObject->setTime(0, 0, 0);
            
            // Check if future date
            $today = new \DateTime();
            $today->setTime(23, 59, 59);
            
            if ($dateObject > $today) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Impossible de marquer la présence pour une date future'
                ], 400);
            }
            
            // Check if weekend
            $dayOfWeek = (int)$dateObject->format('N');
            if ($dayOfWeek >= 6) {
                return new JsonResponse([
                    'success' => false, 
                    'message' => 'Impossible de marquer la présence pour les weekends'
                ], 400);
            }
            
            // Get or create absence record
            $absence = $this->absenceRepository->findOneBy([
                'idEmploye' => $employeeId,
                'date' => $dateObject,
            ]);
            
            if (!$absence) {
                $absence = new Absence();
                $absence->setIdEmploye($employeeId);
                $absence->setDate($dateObject);
            }
            
            // Set status
            $absenceStatus = AbsenceStatus::from($status);
            $absence->setStatus($absenceStatus);
            
            // Save
            $this->entityManager->persist($absence);
            $this->entityManager->flush();
            
            // Get employee information for SMS
            $employee = $this->userRepository->find($employeeId);
            $employeeName = $employee->getFirstname() . ' ' . $employee->getLastname();
            $formattedDate = $dateObject->format('d/m/Y');
            $statusName = $absenceStatus->name;
            
            // Send SMS to HR
            $this->sendSmsToHR($employeeName, $formattedDate, $statusName);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Présence marquée avec succès pour le ' . $dateObject->format('d/m/Y')
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send SMS notification to HR about employee attendance
     */
    private function sendSmsToHR(string $employeeName, string $date, string $status): void
    {
        try {
            // Get Twilio credentials from environment
            $accountSid = $_ENV['MTWILIO_ACCOUNT_SID'];
            $authToken = $_ENV['MTWILIO_AUTH_TOKEN'];
            $twilioNumber = $_ENV['MTWILIO_PHONE_NUMBER'];
            $hrNumber = $_ENV['MHR_PHONE_NUMBER'];
            
            // Create Twilio client
            $client = new Client($accountSid, $authToken);
            
            // Prepare message content
            $messageBody = "L'employé $employeeName a marqué sa présence comme '$status' pour le $date. Veuillez valider cette entrée.";
            
            // Send the SMS
            $client->messages->create(
                $hrNumber,
                [
                    'from' => $twilioNumber,
                    'body' => $messageBody
                ]
            );
        } catch (\Exception $e) {
            // Log the error but don't interrupt the flow
            error_log('SMS Error: ' . $e->getMessage());
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

    #[Route('/report/{month}/{year}', name: 'app_employee_attendance_report')]
public function attendanceReport(Request $request, string $month = null, int $year = null): Response
{
    $user = $this->getCurrentUser();
    $employee = $user;
    
    // Default to current month/year if not specified
    if ($month === null) {
        $month = date('F');
    }
    if ($year === null) {
        $year = (int)date('Y');
    }
    
    // Convert month name to number
    $monthNumber = date('m', strtotime("$month 1, $year"));
    
    // Get previous month
    $prevMonthDate = new \DateTime("$year-$monthNumber-01");
    $prevMonthDate->modify('-1 month');
    $prevMonth = $prevMonthDate->format('F');
    $prevMonthYear = (int)$prevMonthDate->format('Y');
    $prevMonthNumber = (int)$prevMonthDate->format('m');
    
    // Get attendance data for current month
    $stats = $this->getMonthlyStats($employee->getId(), $monthNumber, $year);
    
    // Get attendance data for previous month
    $prevStats = $this->getMonthlyStats($employee->getId(), $prevMonthNumber, $prevMonthYear);
    
    // Get attendance history for last 6 months
    $historyData = $this->getHistoryData($employee->getId(), 6);
    
    // Generate monthly calendar
    $calendar = $this->generateCalendar($monthNumber, $year, $employee->getId());
    
    // Sample notes (replace with actual notes from your system)
    $notes = [
        [
            'date' => new \DateTime(),
            'comment' => 'Excellent attendance record this month.',
            'author' => 'HR Manager'
        ]
    ];
    
    // Prepare common template parameters
    $templateParams = [
        'employee' => $employee,
        'month' => $month,
        'year' => $year,
        'stats' => $stats,
        'prevStats' => $prevStats,
        'prevMonth' => $prevMonth,
        'prevMonthYear' => $prevMonthYear,
        'calendar' => $calendar,
        'historyLabels' => $historyData['labels'],
        'historyData' => $historyData['data'],
        'notes' => $notes
    ];
    
    // Check if PDF export is requested
    if ($request->query->has('pdf')) {
        try {
            // Template path for PDF
            $templatePath = 'absence/employeAttendancePDF.html.twig';
            
            // PDF specific options
            $pdfOptions = [
                'isRemoteEnabled' => true,
                'paperSize' => 'A4',
                'paperOrientation' => 'portrait',
                'options' => [
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Arial',
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled' => false,
                    'dpi' => 120,
                    'defaultMediaType' => 'screen',
                    'defaultPaperSize' => 'A4',
                    'defaultPaperOrientation' => 'portrait'
                ]
            ];
            
            // Generate PDF using our service
            $pdfContent = $this->pdfService->generatePdfFromTemplate(
                $templatePath,
                $templateParams,
                "attendance_report_{$employee->getLastname()}_{$month}_{$year}.pdf",
                $pdfOptions
            );
            
            // Create response with PDF content
            $response = new Response($pdfContent);
            
            // Set proper headers for PDF download
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                "attendance_report_{$employee->getLastname()}_{$month}_{$year}.pdf"
            );
            
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', $disposition);
            
            return $response;
        } catch (\Exception $e) {
            // Log error and display flash message
            $this->addFlash('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
            
            // Redirect to regular report page
            return $this->redirectToRoute('app_employee_attendance_report', [
                'month' => $month,
                'year' => $year
            ]);
        }
    }
    
    // Regular HTML response (not PDF)
    return $this->render('absence/employeAttendancePDF.html.twig', $templateParams);
}
    
    /**
     * Generate month calendar with absence statuses
     */
    private function generateCalendar(int $monthNumber, int $year, int $employeeId): array
    {
        $calendar = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNumber, $year);
        $firstDay = new \DateTime("$year-$monthNumber-01");
        $firstDayOfWeek = (int)$firstDay->format('N');
        
        // Get all absences for the month
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
        
        // Create lookup array for quick access to absences by day
        $absencesByDay = [];
        foreach ($absences as $absence) {
            $day = (int)$absence->getDate()->format('j');
            $absencesByDay[$day] = $absence->getStatus()->name;
        }
        
        // Create calendar weeks
        $currentWeek = [];
        $weekCounter = 0;
        
        // Add empty cells for days of the week before the 1st
        for ($i = 1; $i < $firstDayOfWeek; $i++) {
            $currentWeek[] = ['day' => null, 'weekday' => $i, 'status' => null];
        }
        
        // Add days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = new \DateTime("$year-$monthNumber-$day");
            $weekday = (int)$date->format('N');
            
            $currentWeek[] = [
                'day' => $day,
                'weekday' => $weekday,
                'status' => $absencesByDay[$day] ?? null
            ];
            
            // Start new week on Sunday or when month ends
            if ($weekday === 7 || $day === $daysInMonth) {
                // If this is the last day and not Sunday, add empty cells
                if ($day === $daysInMonth && $weekday < 7) {
                    for ($i = $weekday + 1; $i <= 7; $i++) {
                        $currentWeek[] = ['day' => null, 'weekday' => $i, 'status' => null];
                    }
                }
                
                $calendar[] = $currentWeek;
                $currentWeek = [];
            }
        }
        
        return $calendar;
    }
    
    /**
     * Get monthly attendance statistics
     */
    private function getMonthlyStats(int $employeeId, int $monthNumber, int $year): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNumber, $year);
        $workDays = 0;
        
        // Count working days (exclude weekends)
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = new \DateTime("$year-$monthNumber-$day");
            $weekday = (int)$date->format('N');
            
            if ($weekday < 6) { // 1-5 are weekdays
                $workDays++;
            }
        }
        
        // Get absences for the month
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
        
        // Initialize counters
        $present = 0;
        $absent = 0;
        $leave = 0;
        
        foreach ($absences as $absence) {
            switch ($absence->getStatus()) {
                case AbsenceStatus::PRESENT:
                    $present++;
                    break;
                case AbsenceStatus::ABSENT:
                    $absent++;
                    break;
                case AbsenceStatus::EN_CONGE:
                    $leave++;
                    break;
            }
        }
        
        // Calculate attendance rate
        $attendanceRate = $workDays > 0 
            ? round(($present / $workDays) * 100, 1) 
            : 0;
        
        return [
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'workDays' => $workDays,
            'attendanceRate' => $attendanceRate
        ];
    }
    
    /**
     * Get historical attendance data for charts
     */
    private function getHistoryData(int $employeeId, int $months = 6): array
    {
        $historyLabels = [];
        $historyData = [
            'present' => [],
            'absent' => [],
            'leave' => []
        ];
        
        $currentDate = new \DateTime();
        
        // Loop through the last X months
        for ($i = $months - 1; $i >= 0; $i--) {
            $targetDate = clone $currentDate;
            $targetDate->modify("-$i month");
            
            $monthNumber = (int)$targetDate->format('m');
            $year = (int)$targetDate->format('Y');
            $monthName = $targetDate->format('M Y');
            
            $historyLabels[] = $monthName;
            
            $stats = $this->getMonthlyStats($employeeId, $monthNumber, $year);
            
            $historyData['present'][] = $stats['present'];
            $historyData['absent'][] = $stats['absent'];
            $historyData['leave'][] = $stats['leave'];
        }
        
        return [
            'labels' => $historyLabels,
            'data' => $historyData
        ];
    }
}