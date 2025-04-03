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

class TacheController extends AbstractController
{
    #[Route('/projets/{id}/taches', name: 'taches_list')]
    public function index(int $id, ProjetRepository $projetRepository, TacheRepository $tacheRepository): Response
    {
        $projet = $projetRepository->find($id);

        if (!$projet) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        $taches = $tacheRepository->findBy(['projet' => $projet]);
        $form = $this->createForm(TacheType::class, new Tache());

        return $this->render('tache/list.html.twig', [
            'projet' => $projet,
            'taches' => $taches,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/projets/{id}/taches/ajouter', name: 'taches_add', methods: ['POST'])]
    public function add(int $id, Request $request, ProjetRepository $projetRepository, TacheRepository $tacheRepository, EntityManagerInterface $entityManager): Response
    {
        $projet = $projetRepository->find($id);

        if (!$projet) {
            throw $this->createNotFoundException('Projet introuvable.');
        }
        $idEmploye = 87;
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tache->setCreatedAt(new \DateTime());
            $tache->setProjet($projet); // Associer la tâche au projet
            $tache->setIdEmploye($idEmploye);
            $entityManager->persist($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        // Si le formulaire n'est pas valide, récupérer les tâches existantes
        $taches = $tacheRepository->findBy(['projet' => $projet]);

        return $this->render('tache/list.html.twig', [
            'projet' => $projet,
            'taches' => $taches,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/projets/{id}/taches/supprimer/{tacheId}', name: 'taches_delete', methods: ['POST'])]
    public function deleteTache(int $id, int $tacheId, TacheRepository $tacheRepository, EntityManagerInterface $entityManager): Response
    {
        $tache = $tacheRepository->find($tacheId);

        if (!$tache || $tache->getProjet()->getId() !== $id) {
            $this->addFlash('error', 'Tâche introuvable ou non associée à ce projet.');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        $entityManager->remove($tache);
        $entityManager->flush();
        $this->addFlash('success', 'Tâche supprimée avec succès.');

        return $this->redirectToRoute('taches_list', ['id' => $id]);
    }

    #[Route('/projets/{id}/taches/edit/{tacheId}', name: 'taches_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, int $tacheId, Request $request, TacheRepository $tacheRepository, ProjetRepository $projetRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la tâche et le projet
        $tache = $tacheRepository->find($tacheId);
        $projet = $projetRepository->find($id);

        // Vérifier si la tâche et le projet existent et si la tâche est bien associée au projet
        if (!$tache || !$projet || $tache->getProjet()->getId() !== $id) {
            $this->addFlash('error', 'Tâche ou projet introuvable.');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        // Récupérer toutes les tâches associées au projet
        $taches = $tacheRepository->findBy(['projet' => $projet]);

        // Créer le formulaire d'édition
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Tâche modifiée avec succès !');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        return $this->render('tache/list.html.twig', [
            'form' => $form->createView(),
            'tache_id' => $tacheId,
            'editMode' => true,
            'projet' => $projet,
            'taches' => $taches,
        ]);
    }
}
