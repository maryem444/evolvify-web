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

#[Route('/employees/attendance')]
class AbsenceController extends AbstractController
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

    #[Route('/admin', name: 'app_employee_attendance_admin')]
    public function index(Request $request): Response
    {
        // Get month and year from request, default to current month/year
        $month = $request->query->get('month', date('F'));
        $year = $request->query->get('year', date('Y'));
        
        // Convert month name to number
        $monthNumber = date('m', strtotime($month));
        
        // Calculate days in month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNumber, $year);
        
        // Generate array of days for the month (1-31)
        $days = range(1, $daysInMonth);
        
        // Get first day of the week for this month
        $firstDayOfMonth = date('N', strtotime("$year-$monthNumber-01"));
        
        // Get all employees
        $employees = $this->userRepository->findAll();
        
        // Prepare attendance data structure
        $attendanceData = [];
        
        foreach ($employees as $employee) {
            $attendanceData[$employee->getId()] = [
                'employee' => $employee,
                'days' => [],
            ];
            
            // Initialize all days
            foreach ($days as $day) {
                $attendanceData[$employee->getId()]['days'][$day] = null;
            }
        }
        
        // Get all absences for the month
        $startDate = new \DateTime("$year-$monthNumber-01");
        $endDate = new \DateTime("$year-$monthNumber-$daysInMonth");
        
        $absences = $this->absenceRepository->createQueryBuilder('a')
            ->where('a.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
        
        // Populate attendance data with actual statuses
        foreach ($absences as $absence) {
            $employeeId = $absence->getIdEmploye();
            $day = (int)$absence->getDate()->format('j'); // Day without leading zeros
            
            if (isset($attendanceData[$employeeId])) {
                $attendanceData[$employeeId]['days'][$day] = $absence;
            }
        }
        
        // Get all possible status options
        $statusOptions = AbsenceStatus::cases();
        
        return $this->render('absence/admin.html.twig', [
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'attendanceData' => $attendanceData,
            'statusOptions' => $statusOptions,
        ]);
    }

    #[Route('/update', name: 'app_employee_attendance_update', methods: ['POST'])]
    public function updateAttendance(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['employeeId'], $data['date'], $data['status'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required parameters'], 400);
        }
        
        $employeeId = $data['employeeId'];
        $date = new \DateTime($data['date']);
        $status = $data['status'];
        
        try {
            // Check if there's already an absence record for this employee on this date
            $absence = $this->absenceRepository->findOneBy([
                'idEmploye' => $employeeId,
                'date' => $date,
            ]);
            
            if (!$absence) {
                // Create a new absence record if none exists
                $absence = new Absence();
                $absence->setIdEmploye($employeeId);
                $absence->setDate($date);
            }
            
            // Set the status
            $absenceStatus = AbsenceStatus::from($status);
            $absence->setStatus($absenceStatus);
            
            // Save changes
            $this->entityManager->persist($absence);
            $this->entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/export', name: 'app_employee_attendance_export')]
    public function exportAttendance(Request $request): Response
    {
        $month = $request->query->get('month', date('F'));
        $year = $request->query->get('year', date('Y'));
        
        // Logic to generate attendance report for export
        $monthNumber = date('m', strtotime($month));
        
        $startDate = new \DateTime("$year-$monthNumber-01");
        $endDate = new \DateTime($startDate->format('Y-m-t'));
        
        $absences = $this->absenceRepository->createQueryBuilder('a')
            ->where('a.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
        
        // Generate CSV content
        $csvContent = "Employee ID,Date,Status\n";
        
        foreach ($absences as $absence) {
            $csvContent .= sprintf(
                "%s,%s,%s\n",
                $absence->getIdEmploye(),
                $absence->getDate()->format('Y-m-d'),
                $absence->getStatus()->value
            );
        }
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="attendance_report_' . $month . '_' . $year . '.csv"');
        
        return $response;
    }

    #[Route('/statistics', name: 'app_employee_attendance_statistics')]
    public function statistics(Request $request): Response
    {
        $month = $request->query->get('month', date('F'));
        $year = $request->query->get('year', date('Y'));
        
        $monthNumber = date('m', strtotime($month));
        
        $startDate = new \DateTime("$year-$monthNumber-01");
        $endDate = new \DateTime($startDate->format('Y-m-t'));
        
        // Get all absences for the month
        $absences = $this->absenceRepository->createQueryBuilder('a')
            ->where('a.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
        
        // Calculate statistics
        $statistics = [
            'total' => count($absences),
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'other' => 0,
        ];
        
        foreach ($absences as $absence) {
            switch ($absence->getStatus()) {
                case AbsenceStatus::PRESENT:
                    $statistics['present']++;
                    break;
                case AbsenceStatus::ABSENT:
                    $statistics['absent']++;
                    break;
                case AbsenceStatus::EN_CONGE:
                    $statistics['leave']++;
                    break;
                default:
                    $statistics['other']++;
            }
        }
        
        return $this->render('absence/statistics.html.twig', [
            'month' => $month,
            'year' => $year,
            'statistics' => $statistics,
        ]);
    }
}