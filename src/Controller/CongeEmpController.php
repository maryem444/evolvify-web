<?php

namespace App\Controller;

use App\Entity\Conge;
use App\Entity\CongeStatus;
use App\Entity\CongeType;
use App\Entity\CongeReason;
use App\Entity\User;
use App\Repository\CongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/employe/conge')]
class CongeEmpController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CongeRepository $congeRepository;
    // Define a constant for the employee ID
    private const EMPLOYEE_ID = 87;

    public function __construct(EntityManagerInterface $entityManager, CongeRepository $congeRepository)
    {
        $this->entityManager = $entityManager;
        $this->congeRepository = $congeRepository;
    }

    #[Route('/', name: 'app_employe_conge_index', methods: ['GET'])]
    public function index(): Response
    {
        // Use static employee ID instead of getting it from the user
        $userId = self::EMPLOYEE_ID;
        
        // Récupérer uniquement les congés de l'employé connecté
        $conges = $this->congeRepository->findBy(['idEmploye' => $userId]);
        
        // Calculer les statistiques
        $congesPris = count(array_filter($conges, function($conge) {
            return $conge->getStatus() === CongeStatus::ACCEPTE;
        }));
        
        $demandesEnAttente = count(array_filter($conges, function($conge) {
            return $conge->getStatus() === CongeStatus::EN_COURS;
        }));
        
        // For static implementation, hardcode the remaining days or query from database
        $joursRestants = 25; // Example value - replace with actual value as needed
        
        return $this->render('conges/indexEmp.html.twig', [
            'conges' => $conges,
            'congesPris' => $congesPris,
            'demandesEnAttente' => $demandesEnAttente,
            'joursRestants' => $joursRestants,
        ]);
    }

    #[Route('/new', name: 'app_employe_conge_new', methods: ['GET', 'POST'])]
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
            
            // Use static employee ID
            $conge->setIdEmploye(self::EMPLOYEE_ID);
            
            $conge->setType(CongeType::from($request->request->get('type')));
            $conge->setReason(CongeReason::from($request->request->get('reason')));
            $conge->setDescription($request->request->get('description'));
            
            $this->entityManager->persist($conge);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé créée avec succès!');
            
            return $this->redirectToRoute('app_employe_conge_index');
        }
        
        // Get all enum values for dropdowns
        $types = $this->getEnumValues(CongeType::class);
        $reasons = $this->getEnumValues(CongeReason::class);
        
        return $this->render('conges/newEmp.html.twig', [
            'types' => $types,
            'reasons' => $reasons,
        ]);
    }

    #[Route('/{id}', name: 'app_employe_conge_show', methods: ['GET'])]
    public function show(Conge $conge): Response
    {
        // Vérifier que l'ID employé de la demande correspond à notre ID statique
        if ($conge->getIdEmploye() !== self::EMPLOYEE_ID) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette demande de congé.');
        }
        
        return $this->render('conges/showEmp.html.twig', [
            'conge' => $conge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_employe_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conge $conge): Response
    {
        // Vérifier que l'ID employé de la demande correspond à notre ID statique
        if ($conge->getIdEmploye() !== self::EMPLOYEE_ID) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette demande de congé.');
        }
        
        // Vérifier que le congé n'est pas déjà approuvé
        if ($conge->getStatus() === CongeStatus::ACCEPTE) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier une demande de congé déjà approuvée.');
            return $this->redirectToRoute('app_employe_conge_index');
        }
        
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
            $conge->setType(CongeType::from($request->request->get('type')));
            $conge->setReason(CongeReason::from($request->request->get('reason')));
            $conge->setDescription($request->request->get('description'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé modifiée avec succès!');
            
            return $this->redirectToRoute('app_employe_conge_index');
        }
        
        // Get all enum values for dropdowns
        $types = $this->getEnumValues(CongeType::class);
        $reasons = $this->getEnumValues(CongeReason::class);
        
        return $this->render('conges/editEmp.html.twig', [
            'conge' => $conge,
            'types' => $types,
            'reasons' => $reasons,
        ]);
    }

   #[Route('/{id}/status', name: 'app_employe_conge_status', methods: ['POST'])]
public function updateStatus(Request $request, Conge $conge): Response
{
    // Vérifier que l'ID employé de la demande correspond à notre ID statique
    if ($conge->getIdEmploye() !== self::EMPLOYEE_ID) {
        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette demande de congé.');
    }

    $newStatus = CongeStatus::from($request->request->get('status'));

    // Si le statut passe à ACCEPTÉ et que ce n'était pas déjà le cas
    if ($newStatus === CongeStatus::ACCEPTE && $conge->getStatus() !== CongeStatus::ACCEPTE) {
        $user = $this->entityManager->getRepository(User::class)->find($conge->getIdEmploye());

        if ($user) {
            $joursRestants = $user->getCongeRestant() ?? 0;
            $joursDemandes = $conge->getNumberOfDays();

            // Déduire les jours demandés, sans passer en négatif
            $user->setCongeRestant(max(0, $joursRestants - $joursDemandes));
            $this->entityManager->persist($user);
        }
    }

    // Mettre à jour le statut du congé
    $conge->setStatus($newStatus);
    $this->entityManager->flush();

    $this->addFlash('success', 'Statut de congé mis à jour avec succès!');

    return $this->redirectToRoute('app_employe_conge_index');
}


    #[Route('/{id}', name: 'app_employe_conge_delete', methods: ['POST'])]
    public function delete(Request $request, Conge $conge): Response
    {
        // Vérifier que l'ID employé de la demande correspond à notre ID statique
        if ($conge->getIdEmploye() !== self::EMPLOYEE_ID) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette demande de congé.');
        }
        
        // Vérifier que le congé n'est pas déjà approuvé
        if ($conge->getStatus() === CongeStatus::ACCEPTE) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer une demande de congé déjà approuvée.');
            return $this->redirectToRoute('app_employe_conge_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$conge->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($conge);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé supprimée avec succès!');
        }

        return $this->redirectToRoute('app_employe_conge_index');
    }
    
    #[Route('/api/list', name: 'app_employe_conge_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        // Use static employee ID
        $userId = self::EMPLOYEE_ID;
        
        // Récupérer uniquement les congés de l'employé connecté
        $conges = $this->congeRepository->findBy(['idEmploye' => $userId]);
        $data = [];
        
        foreach ($conges as $conge) {
            $data[] = [
                'id' => $conge->getId(),
                'employee' => $conge->getIdEmploye(),
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
    
    #[Route('/dashboard', name: 'app_employe_conge_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->redirectToRoute('app_employe_conge_index');
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