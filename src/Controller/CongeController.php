<?php

namespace App\Controller;

use App\Entity\Conge;
use App\Entity\CongeStatus;
use App\Entity\CongeType;
use App\Entity\CongeReason;
use App\Entity\User;
use App\Repository\CongeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twilio\Rest\Client;

#[Route('/conge')]
class CongeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CongeRepository $congeRepository;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager, 
        CongeRepository $congeRepository,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->congeRepository = $congeRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'app_conge_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        try {
            // Paramètres de filtre
            $searchTerm = $request->query->get('search', '');
            $status = $request->query->get('status', '');
            $type = $request->query->get('type', '');
            $reason = $request->query->get('reason', '');
            
            // Paramètres de pagination
            $page = max(1, $request->query->getInt('page', 1));
            $limit = 10; // Nombre d'éléments par page
            
            // Récupérer les congés filtrés et paginés
            $congesData = $this->congeRepository->findFilteredAndPaginated(
                $searchTerm,
                $status ? CongeStatus::from($status) : null,
                $type ? CongeType::from($type) : null,
                $reason ? CongeReason::from($reason) : null,
                $page,
                $limit
            );
            
            $conges = $congesData['results'];
            $totalItems = $congesData['totalItems'];
            $maxPages = ceil($totalItems / $limit);
            
            // Get all enum values for dropdowns
            $statuses = $this->getEnumValues(CongeStatus::class);
            $types = $this->getEnumValues(CongeType::class);
            $reasons = $this->getEnumValues(CongeReason::class);
            
            return $this->render('conges/index.html.twig', [
                'conges' => $conges,
                'searchTerm' => $searchTerm,
                'selectedStatus' => $status,
                'selectedType' => $type,
                'selectedReason' => $reason,
                'statuses' => $statuses,
                'types' => $types,
                'reasons' => $reasons,
                'currentPage' => $page,
                'maxPages' => $maxPages,
                'totalItems' => $totalItems,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors du chargement des congés: ' . $e->getMessage());
            return $this->render('conges/index.html.twig', [
                'conges' => [],
                'searchTerm' => '',
                'selectedStatus' => '',
                'selectedType' => '',
                'selectedReason' => '',
                'statuses' => $this->getEnumValues(CongeStatus::class),
                'types' => $this->getEnumValues(CongeType::class),
                'reasons' => $this->getEnumValues(CongeReason::class),
                'currentPage' => 1,
                'maxPages' => 1,
                'totalItems' => 0,
                'limit' => 10
            ]);
        }
    }

    #[Route('/new', name: 'app_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $conge = new Conge();
            
            // Process form data
            $leaveStart = new \DateTime($request->request->get('leaveStart'));
            $leaveEnd = new \DateTime($request->request->get('leaveEnd'));
            
            // Calculate days
            $interval = $leaveStart->diff($leaveEnd);
            $numberOfDays = $interval->days + 1; // Include both start and end days
            
            $conge->setLeaveStart($leaveStart);
            $conge->setLeaveEnd($leaveEnd);
            $conge->setNumberOfDays($numberOfDays);
            $conge->setStatus(CongeStatus::EN_COURS);
            
            // Récupérer l'utilisateur complet, pas seulement l'ID
            $employeId = $request->request->get('idEmploye');
            $employe = $this->userRepository->find($employeId);
            
            if (!$employe) {
                throw $this->createNotFoundException('Employé non trouvé');
            }
            
            $conge->setEmploye($employe);
            $conge->setType(CongeType::from($request->request->get('type')));
            $conge->setReason(CongeReason::from($request->request->get('reason')));
            $conge->setDescription($request->request->get('description'));
            
            $this->entityManager->persist($conge);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé créée avec succès!');
            
            return $this->redirectToRoute('app_conge_index');
        }
        
        // Get all enum values for dropdowns
        $types = $this->getEnumValues(CongeType::class);
        $reasons = $this->getEnumValues(CongeReason::class);
        
        // Récupérer tous les employés pour le dropdown
        $employes = $this->userRepository->findAll();
        
        return $this->render('conges/new.html.twig', [
            'types' => $types,
            'reasons' => $reasons,
            'employes' => $employes,
        ]);
    }

    #[Route('/{id}', name: 'app_conge_show', methods: ['GET'])]
    public function show(Conge $conge): Response
    {
        try {
            // Assurez-vous que l'employé est chargé si nécessaire
            if ($conge->getEmploye() === null && $conge->getIdEmploye() !== null) {
                $employe = $this->userRepository->find($conge->getIdEmploye());
                if ($employe) {
                    $conge->setEmploye($employe);
                }
            }
            
            return $this->render('conges/show.html.twig', [
                'conge' => $conge,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue: ' . $e->getMessage());
            return $this->redirectToRoute('app_conge_index');
        }
    }

    #[Route('/{id}/edit', name: 'app_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conge $conge): Response
    {
        if ($request->isMethod('POST')) {
            // Process form data
            $leaveStart = new \DateTime($request->request->get('leaveStart'));
            $leaveEnd = new \DateTime($request->request->get('leaveEnd'));
            
            // Calculate days
            $interval = $leaveStart->diff($leaveEnd);
            $numberOfDays = $interval->days + 1; // Include both start and end days
            
            $conge->setLeaveStart($leaveStart);
            $conge->setLeaveEnd($leaveEnd);
            $conge->setNumberOfDays($numberOfDays);
            
            // Récupérer l'utilisateur complet si l'ID a été modifié
            $employeId = $request->request->get('idEmploye');
            $employe = $this->userRepository->find($employeId);
            
            if (!$employe) {
                throw $this->createNotFoundException('Employé non trouvé');
            }
            
            $conge->setEmploye($employe);
            $conge->setType(CongeType::from($request->request->get('type')));
            $conge->setReason(CongeReason::from($request->request->get('reason')));
            $conge->setDescription($request->request->get('description'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé modifiée avec succès!');
            
            return $this->redirectToRoute('app_conge_index');
        }
        
        // Get all enum values for dropdowns
        $types = $this->getEnumValues(CongeType::class);
        $reasons = $this->getEnumValues(CongeReason::class);
        
        // Récupérer tous les employés pour le dropdown
        $employes = $this->userRepository->findAll();
        
        return $this->render('conges/edit.html.twig', [
            'conge' => $conge,
            'types' => $types,
            'reasons' => $reasons,
            'employes' => $employes,
        ]);
    }

    #[Route('/{id}/status', name: 'app_conge_status', methods: ['POST'])]
    public function updateStatus(Request $request, Conge $conge): Response
    {
        $status = CongeStatus::from($request->request->get('status'));
        $oldStatus = $conge->getStatus();
        $conge->setStatus($status);
        
        $this->entityManager->flush();
        
        // Send SMS notification if status changed to APPROUVE or REFUSE
        if (($status === CongeStatus::ACCEPTE || $status === CongeStatus::REFUSE) && $status !== $oldStatus) {
            $this->sendStatusNotification($conge);
        }
        
        $this->addFlash('success', 'Statut de congé mis à jour avec succès!');
        
        return $this->redirectToRoute('app_conge_index');
    }

    #[Route('/{id}', name: 'app_conge_delete', methods: ['POST'])]
    public function delete(Request $request, Conge $conge): Response
    {
        if ($this->isCsrfTokenValid('delete'.$conge->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($conge);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé supprimée avec succès!');
        }

        return $this->redirectToRoute('app_conge_index');
    }
    
    #[Route('/api/list', name: 'app_conge_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $conges = $this->congeRepository->findAllWithEmployees();
        $data = [];
        
        foreach ($conges as $conge) {
            // Récupérer l'employé directement depuis la relation
            $employe = $conge->getEmploye();
            $employeNom = $employe ? $employe->getFirstname() . ' ' . $employe->getLastname() : 'N/A';
            
            $data[] = [
                'id' => $conge->getId(),
                'employee_id' => $conge->getIdEmploye(), // Pour compatibilité
                'employee_name' => $employeNom, // Nouveau champ avec le nom complet
                'leaveStart' => $conge->getLeaveStart()->format('Y-m-d'),
                'leaveEnd' => $conge->getLeaveEnd()->format('Y-m-d'),
                'numberOfDays' => $conge->getNumberOfDays(),
                'status' => $conge->getStatus()->getLabel(),
                'type' => $conge->getType()->getLabel(),
                'reason' => $conge->getReason()->getLabel(),
                'description' => $conge->getDescription(),
            ];
        }
        
        return new JsonResponse($data);
    }
    
   /**
     * Send SMS notification to HR about leave request status change
     */
    private function sendStatusNotification(Conge $conge): void
    {
        try {
            // Check if Twilio credentials are configured
            if (!isset($_ENV['MTWILIO_ACCOUNT_SID']) || !isset($_ENV['MTWILIO_AUTH_TOKEN']) || !isset($_ENV['MTWILIO_PHONE_NUMBER'])) {
                $this->addFlash('warning', 'Configuration Twilio manquante. SMS non envoyé.');
                error_log('Missing Twilio configuration. SMS not sent.');
                return;
            }

            // Get HR phone number - check if it's set
            $hrPhoneNumber = $_ENV['MHR_PHONE_NUMBER'] ?? null;
            if (!$hrPhoneNumber) {
                error_log('HR phone number not configured. SMS notification skipped.');
                $this->addFlash('warning', 'Numéro de téléphone RH non configuré. Notification SMS ignorée.');
                return;
            }
            
            $employe = $conge->getEmploye();
            if (!$employe) {
                error_log('Employee data missing. SMS not sent.');
                $this->addFlash('warning', 'Données employé manquantes. SMS non envoyé.');
                return;
            }
            
            // Prepare message data
            $status = $conge->getStatus();
            $employeName = $employe->getFirstname() . ' ' . $employe->getLastname();
            $startDate = $conge->getLeaveStart()->format('d/m/Y');
            $endDate = $conge->getLeaveEnd()->format('d/m/Y');
            
            // Twilio credentials
            $twilioSid = $_ENV['MTWILIO_ACCOUNT_SID'];
            $twilioToken = $_ENV['MTWILIO_AUTH_TOKEN'];
            $twilioNumber = $_ENV['MTWILIO_PHONE_NUMBER'];
            
            // Create Twilio client
            $twilio = new Client($twilioSid, $twilioToken);
            
            // Only send notification to HR
            try {
                // Prepare HR message
                $hrMessage = "Notification: La demande de congé de $employeName du $startDate au $endDate a été marquée comme " . 
                         ($status === CongeStatus::ACCEPTE ? "APPROUVÉE" : "REFUSÉE");
                
                // Add debug output - remove in production
                error_log("SMS Details - SID: {$twilioSid}, Number: {$twilioNumber}, To: {$hrPhoneNumber}");
                error_log("Message content: {$hrMessage}");
                
                // Log attempt             
                error_log("Sending SMS to HR at $hrPhoneNumber");
                
                // Send SMS to HR and get response for logging
                $hrMsgResponse = $twilio->messages->create(
                    $hrPhoneNumber,
                    [
                        'from' => $twilioNumber,
                        'body' => $hrMessage
                    ]
                );
                
                // Log success with message SID
                error_log("SMS sent to HR successfully. SID: " . $hrMsgResponse->sid);
                $this->addFlash('success', 'SMS de notification envoyé à l employé .');
                
            } catch (\Exception $e) {
                error_log('Error sending SMS to HR: ' . $e->getMessage());
                $this->addFlash('danger', 'Erreur lors de l\'envoi du SMS à l employé: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            // Log the overall error
            error_log('Error in SMS notification process: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            $this->addFlash('danger', 'Erreur lors du processus de notification SMS: ' . $e->getMessage());
        }
    }
    
    /**
     * Format phone number to ensure it has international format
     */
    private function formatPhoneNumber(int $phoneNumber): string
    {
        $phoneStr = (string) $phoneNumber;
        
        // If it doesn't start with '+', add Tunisian country code
        if (substr($phoneStr, 0, 1) !== '+') {
            // For Tunisian numbers
            if (strlen($phoneStr) === 8) {
                return '+216' . $phoneStr;
            }
        }
        
        // Return as is if it already has country code
        return $phoneStr;
    }
    
    /**
     * Helper method to get all values from an enum
     */
    private function getEnumValues(string $enumClass): array
    {
        $cases = $enumClass::cases();
        $values = [];
        
        foreach ($cases as $case) {
            $values[$case->value] = $case->getLabel();
        }
        
        return $values;
    }
}