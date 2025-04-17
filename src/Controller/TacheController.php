<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Entity\User;
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

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non connecté.');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tache->setCreatedAt(new \DateTime());
            $tache->setProjet($projet);
            $tache->setUser($user); // ✅ associer l'utilisateur connecté

            $entityManager->persist($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        $taches = $tacheRepository->findBy(['projet' => $projet]);

        return $this->render('tache/list.html.twig', [
            'projet' => $projet,
            'taches' => $taches,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/projets/{id}/taches/supprimer/{tacheId}', name: 'taches_delete', methods: ['POST'])]
    public function deleteTache(
        int $id,
        int $tacheId,
        TacheRepository $tacheRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $tache = $tacheRepository->find($tacheId);
        $user = $this->getUser();
    
        if (
            !$tache ||
            $tache->getProjet()->getId() !== $id ||
            !$user ||
            $tache->getUser() !== $user // ✅ Vérifie que l'utilisateur est le créateur
        ) {
            $this->addFlash('error', 'Suppression non autorisée.');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }
    
        $entityManager->remove($tache);
        $entityManager->flush();
        $this->addFlash('success', 'Tâche supprimée avec succès.');
    
        return $this->redirectToRoute('taches_list', ['id' => $id]);
    }
    

    #[Route('/projets/{id}/taches/edit/{tacheId}', name: 'taches_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        int $tacheId,
        Request $request,
        TacheRepository $tacheRepository,
        ProjetRepository $projetRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $tache = $tacheRepository->find($tacheId);
        $projet = $projetRepository->find($id);
        $user = $this->getUser();

        if (
            !$tache ||
            !$projet ||
            $tache->getProjet()->getId() !== $id ||
            !$user ||
            $tache->getUser() !== $user // ✅ l'utilisateur connecté est bien l'auteur ?
        ) {
            $this->addFlash('error', 'accès non autorisé pour modifier cette tâche.');
            return $this->redirectToRoute('taches_list', ['id' => $id]);
        }

        $taches = $tacheRepository->findBy(['projet' => $projet]);
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
