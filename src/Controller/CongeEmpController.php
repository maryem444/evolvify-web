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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/employe/conge')]
class CongeEmpController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CongeRepository $congeRepository;
    private ValidatorInterface $validator;
    
    // Constante pour définir le nombre total de jours de congé disponibles par an
    private const TOTAL_CONGE_DAYS = 26;

    public function __construct(
        EntityManagerInterface $entityManager, 
        CongeRepository $congeRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->congeRepository = $congeRepository;
        $this->validator = $validator;
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

    /**
     * Recalcule le nombre de jours de congé restants pour un utilisateur
     */
    private function recalculateRemainingDays(User $user): int
    {
        // Utiliser la constante pour le nombre total de jours de congé (26 jours)
        $totalDays = self::TOTAL_CONGE_DAYS;
        
        // Récupérer tous les congés acceptés de type CONGE
        $acceptedLeaves = $this->congeRepository->findBy([
            'employe' => $user,
            'statusString' => CongeStatus::ACCEPTE->value,
            'typeString' => CongeType::CONGE->value
        ]);
        
        // Somme des jours de congé pris
        $usedDays = array_reduce($acceptedLeaves, function($carry, $conge) {
            return $carry + ($conge->getNumberOfDays() ?? 0);
        }, 0);
        
        // Calculer les jours restants
        $remainingDays = $totalDays - $usedDays;
        
        // S'assurer que la valeur n'est pas négative
        return max(0, $remainingDays);
    }

    #[Route('/', name: 'app_employe_conge_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getCurrentUser();
        
        // S'assurer que congeRestant est initialisé et à jour
        if ($user->getCongeRestant() === null) {
            // Initialiser avec le nombre total de jours de congé
            $user->setCongeRestant(self::TOTAL_CONGE_DAYS);
        } else {
            // Recalculer les jours restants pour être sûr que c'est à jour
            $remainingDays = $this->recalculateRemainingDays($user);
            $user->setCongeRestant($remainingDays);
        }
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Récupérer les congés de l'employé connecté
        $conges = $this->congeRepository->findByEmployee($user);
        
        // Calculer les statistiques
        $congesPris = count(array_filter($conges, function($conge) {
            return $conge->getStatus() === CongeStatus::ACCEPTE;
        }));
        
        $demandesEnAttente = count(array_filter($conges, function($conge) {
            return $conge->getStatus() === CongeStatus::EN_COURS;
        }));
        
        // Obtenir les jours restants de l'entité utilisateur
        $joursRestants = $user->getCongeRestant();
        
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
        $user = $this->getCurrentUser();
        
        // Recalculer les jours restants pour s'assurer qu'ils sont à jour
        $joursRestants = $this->recalculateRemainingDays($user);
        $user->setCongeRestant($joursRestants);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        if ($request->isMethod('POST')) {
            $conge = new Conge();
            
            try {
                // Process form data
                $leaveStart = new \DateTime($request->request->get('leaveStart'));
                $leaveEnd = new \DateTime($request->request->get('leaveEnd'));
                
                // Calculate days
                $interval = $leaveStart->diff($leaveEnd);
                $numberOfDays = $interval->days + 1; // Include both start and end days
                
                // Vérifier si l'utilisateur a assez de jours de congé
                if ($user->getCongeRestant() < $numberOfDays && $request->request->get('type') === CongeType::CONGE->value) {
                    $this->addFlash('error', 'Vous n\'avez pas assez de jours de congé restants.');
                    
                    // Get all enum values for dropdowns
                    $types = $this->getEnumValues(CongeType::class);
                    $reasons = $this->getEnumValues(CongeReason::class);
                    
                    return $this->render('conges/newEmp.html.twig', [
                        'types' => $types,
                        'reasons' => $reasons,
                        'formData' => $request->request->all(),
                    ]);
                }
                
                $conge->setLeaveStart($leaveStart);
                $conge->setLeaveEnd($leaveEnd);
                $conge->setNumberOfDays($numberOfDays);
                $conge->setStatus(CongeStatus::EN_COURS);
                
                // Set the user entity directly
                $conge->setEmploye($user);
                
                $conge->setType(CongeType::from($request->request->get('type')));
                $conge->setReason(CongeReason::from($request->request->get('reason')));
                $conge->setDescription($request->request->get('description'));
                
                // Validate the entity
                $errors = $this->validator->validate($conge);
                
                if (count($errors) > 0) {
                    // Create error messages for form display
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    
                    // Get all enum values for dropdowns
                    $types = $this->getEnumValues(CongeType::class);
                    $reasons = $this->getEnumValues(CongeReason::class);
                    
                    // Return to form with errors
                    return $this->render('conges/newEmp.html.twig', [
                        'types' => $types,
                        'reasons' => $reasons,
                        'formData' => $request->request->all(),
                    ]);
                }
                
                $this->entityManager->persist($conge);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Demande de congé créée avec succès!');
                
                return $this->redirectToRoute('app_employe_conge_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
                
                // Get all enum values for dropdowns
                $types = $this->getEnumValues(CongeType::class);
                $reasons = $this->getEnumValues(CongeReason::class);
                
                return $this->render('conges/newEmp.html.twig', [
                    'types' => $types,
                    'reasons' => $reasons,
                    'formData' => $request->request->all(),
                ]);
            }
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
        $user = $this->getCurrentUser();
        
        // Vérifier que l'employé de la demande correspond à l'utilisateur connecté
        if ($conge->getEmploye() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette demande de congé.');
        }
        
        return $this->render('conges/showEmp.html.twig', [
            'conge' => $conge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_employe_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conge $conge): Response
    {
        $user = $this->getCurrentUser();
        
        // Recalculer les jours restants pour s'assurer qu'ils sont à jour
        $joursRestants = $this->recalculateRemainingDays($user);
        $user->setCongeRestant($joursRestants);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Vérifier que l'employé de la demande correspond à l'utilisateur connecté
        if ($conge->getEmploye() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette demande de congé.');
        }
        
        // Vérifier que le congé n'est pas déjà approuvé
        if ($conge->getStatus() === CongeStatus::ACCEPTE) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier une demande de congé déjà approuvée.');
            return $this->redirectToRoute('app_employe_conge_index');
        }
        
        if ($request->isMethod('POST')) {
            try {
                // Process form data
                $leaveStart = new \DateTime($request->request->get('leaveStart'));
                $leaveEnd = new \DateTime($request->request->get('leaveEnd'));
                
                // Calculate days
                $interval = $leaveStart->diff($leaveEnd);
                $numberOfDays = $interval->days + 1; // Include both start and end days
                
                // Vérifier si l'utilisateur a assez de jours de congé (pour les congés payés seulement)
                if ($request->request->get('type') === CongeType::CONGE->value) {
                    $oldDays = $conge->getNumberOfDays();
                    $joursRestants = $user->getCongeRestant();
                    
                    // Si le nombre de jours augmente, vérifier si suffisamment de jours sont disponibles
                    if ($numberOfDays > $oldDays && $joursRestants < ($numberOfDays - $oldDays)) {
                        $this->addFlash('error', 'Vous n\'avez pas assez de jours de congé restants.');
                        
                        // Get all enum values for dropdowns
                        $types = $this->getEnumValues(CongeType::class);
                        $reasons = $this->getEnumValues(CongeReason::class);
                        
                        return $this->render('conges/editEmp.html.twig', [
                            'conge' => $conge,
                            'types' => $types,
                            'reasons' => $reasons,
                        ]);
                    }
                }
                
                $conge->setLeaveStart($leaveStart);
                $conge->setLeaveEnd($leaveEnd);
                $conge->setNumberOfDays($numberOfDays);
                $conge->setType(CongeType::from($request->request->get('type')));
                $conge->setReason(CongeReason::from($request->request->get('reason')));
                $conge->setDescription($request->request->get('description'));
                
                // Validate the entity
                $errors = $this->validator->validate($conge);
                
                if (count($errors) > 0) {
                    // Create error messages for form display
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    
                    // Get all enum values for dropdowns
                    $types = $this->getEnumValues(CongeType::class);
                    $reasons = $this->getEnumValues(CongeReason::class);
                    
                    // Return to form with errors
                    return $this->render('conges/editEmp.html.twig', [
                        'conge' => $conge,
                        'types' => $types,
                        'reasons' => $reasons,
                    ]);
                }
                
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Demande de congé modifiée avec succès!');
                
                return $this->redirectToRoute('app_employe_conge_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
                
                // Get all enum values for dropdowns
                $types = $this->getEnumValues(CongeType::class);
                $reasons = $this->getEnumValues(CongeReason::class);
                
                return $this->render('conges/editEmp.html.twig', [
                    'conge' => $conge,
                    'types' => $types,
                    'reasons' => $reasons,
                ]);
            }
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
        $user = $this->getCurrentUser();
        
        // Vérifier que l'employé de la demande correspond à l'utilisateur connecté
        if ($conge->getEmploye() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette demande de congé.');
        }
        
        $newStatus = CongeStatus::from($request->request->get('status'));
        $oldStatus = $conge->getStatus();
        
        // Mettre à jour le statut du congé
        $conge->setStatus($newStatus);
        $this->entityManager->persist($conge);
        
        // Ne déduire des jours que pour les congés payés
        if ($conge->getType() === CongeType::CONGE) {
            // Recalculer les jours de congé directement
            $remainingDays = $this->recalculateRemainingDays($user);
            $user->setCongeRestant($remainingDays);
            $this->entityManager->persist($user);
        }
        
        // Important: flush APRÈS avoir fait toutes les modifications
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Statut de congé mis à jour avec succès!');
        
        return $this->redirectToRoute('app_employe_conge_index');
    }

    #[Route('/{id}', name: 'app_employe_conge_delete', methods: ['POST'])]
    public function delete(Request $request, Conge $conge): Response
    {
        $user = $this->getCurrentUser();
        
        // Vérifier que l'employé de la demande correspond à l'utilisateur connecté
        if ($conge->getEmploye() !== $user) {
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
            
            // Recalculer les jours restants après suppression
            $remainingDays = $this->recalculateRemainingDays($user);
            $user->setCongeRestant($remainingDays);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Demande de congé supprimée avec succès!');
        }

        return $this->redirectToRoute('app_employe_conge_index');
    }
    
    #[Route('/api/list', name: 'app_employe_conge_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $user = $this->getCurrentUser();
        
        // Récupérer uniquement les congés de l'employé connecté
        $conges = $this->congeRepository->findByEmployee($user);
        $data = [];
        
        foreach ($conges as $conge) {
            $data[] = [
                'id' => $conge->getId(),
                'employee' => $conge->getEmploye()->getId(),
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