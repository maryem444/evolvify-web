<?php

namespace App\Controller;

use App\Entity\Conge;
use App\Entity\CongeStatus;
use App\Entity\CongeType;
use App\Entity\CongeReason;
use App\Repository\CongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/conge')]
class CongeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CongeRepository $congeRepository;

    public function __construct(EntityManagerInterface $entityManager, CongeRepository $congeRepository)
    {
        $this->entityManager = $entityManager;
        $this->congeRepository = $congeRepository;
    }

    #[Route('/', name: 'app_conge_index', methods: ['GET'])]
    public function index(): Response
    {
        $conges = $this->congeRepository->findAll();

        return $this->render('conges/index.html.twig', [
            'conges' => $conges,
        ]);
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
            $conge->setIdEmploye($request->request->get('idEmploye'));
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
        
        return $this->render('conges/new.html.twig', [
            'types' => $types,
            'reasons' => $reasons,
        ]);
    }

    #[Route('/{id}', name: 'app_conge_show', methods: ['GET'])]
    public function show(Conge $conge): Response
    {
        return $this->render('conges/show.html.twig', [
            'conge' => $conge,
        ]);
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
        
        return $this->render('conges/edit.html.twig', [
            'conge' => $conge,
            'types' => $types,
            'reasons' => $reasons,
        ]);
    }

    #[Route('/{id}/status', name: 'app_conge_status', methods: ['POST'])]
    public function updateStatus(Request $request, Conge $conge): Response
    {
        $status = CongeStatus::from($request->request->get('status'));
        $conge->setStatus($status);
        
        $this->entityManager->flush();
        
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
        $conges = $this->congeRepository->findAll();
        $data = [];
        
        foreach ($conges as $conge) {
            $data[] = [
                'id' => $conge->getId(),
                'employee' => $conge->getIdEmploye(), // You might want to fetch employee name in a real app
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