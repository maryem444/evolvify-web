<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Tache;
use Symfony\Component\HttpFoundation\Request;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TasksController extends AbstractController
{
  // Dans TasksController

  #[Route('/kanban/projets/{id}/taches', name: 'kanban_tasks_list')]
  public function index(int $id, ProjetRepository $projetRepository, TacheRepository $tacheRepository): Response
  {
    $projet = $projetRepository->find($id);
    if (!$projet) {
      throw $this->createNotFoundException('Projet introuvable.');
    }

    $taches = $tacheRepository->findBy(['projet' => $projet]);

    // Grouper les tâches par statut
    $tasksByStatus = [
      'TO_DO' => [],
      'IN_PROGRESS' => [],
      'DONE' => [],
      'CANCELED' => []
    ];

    foreach ($taches as $tache) {
      $tasksByStatus[$tache->getStatus()->value][] = $tache;
    }

    $form = $this->createForm(TacheType::class, new Tache());

    return $this->render('tache/kanban.html.twig', [
      'projet' => $projet,
      'tasksByStatus' => $tasksByStatus,
      'form' => $form->createView(),
    ]);
  }

  #[Route('/kanban/projets/{id}/taches/ajouter', name: 'kanban_task_add', methods: ['POST'])]
  public function add(int $id, Request $request, ProjetRepository $projetRepository, EntityManagerInterface $entityManager): Response
  {
    $projet = $projetRepository->find($id);
    if (!$projet) {
      throw $this->createNotFoundException('Projet introuvable.');
    }

    $idEmploye = 87; // Remplacer avec l'ID réel de l'employé connecté
    $tache = new Tache();
    $form = $this->createForm(TacheType::class, $tache);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $tache->setCreatedAt(new \DateTime());
      $tache->setProjet($projet);
      $tache->setIdEmploye($idEmploye);
      $entityManager->persist($tache);
      $entityManager->flush();

      $this->addFlash('success', 'Tâche ajoutée avec succès !');

      return $this->redirectToRoute('kanban_tasks_list', ['id' => $id]);
    }

    return $this->render('tache/kanban.html.twig', [
      'projet' => $projet,
      'form' => $form->createView(),
    ]);
  }

  #[Route('/kanban/projets/{id}/taches/supprimer/{tacheId}', name: 'kanban_task_delete', methods: ['POST'])]
  public function deleteTache(int $id, int $tacheId, TacheRepository $tacheRepository, EntityManagerInterface $entityManager): Response
  {
    $tache = $tacheRepository->find($tacheId);

    if (!$tache || $tache->getProjet()->getId() !== $id) {
      $this->addFlash('error', 'Tâche introuvable ou non associée à ce projet.');
      return $this->redirectToRoute('kanban_tasks_list', ['id' => $id]);
    }

    $entityManager->remove($tache);
    $entityManager->flush();
    $this->addFlash('success', 'Tâche supprimée avec succès.');

    return $this->redirectToRoute('kanban_tasks_list', ['id' => $id]);
  }

  #[Route('/kanban/projets/{id}/taches/edit/{tacheId}', name: 'kanban_task_edit', methods: ['GET', 'POST'])]
  public function edit(int $id, int $tacheId, Request $request, TacheRepository $tacheRepository, ProjetRepository $projetRepository, EntityManagerInterface $entityManager): Response
  {
    $tache = $tacheRepository->find($tacheId);
    $projet = $projetRepository->find($id);

    if (!$tache || !$projet || $tache->getProjet()->getId() !== $id) {
      $this->addFlash('error', 'Tâche ou projet introuvable.');
      return $this->redirectToRoute('kanban_tasks_list', ['id' => $id]);
    }

    $form = $this->createForm(TacheType::class, $tache);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->flush();
      $this->addFlash('success', 'Tâche modifiée avec succès !');
      return $this->redirectToRoute('kanban_tasks_list', ['id' => $id]);
    }

    return $this->render('tache/kanban.html.twig', [
      'form' => $form->createView(),
      'tache_id' => $tacheId,
      'editMode' => true,
      'projet' => $projet,
    ]);
  }
}
