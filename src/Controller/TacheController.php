<?php

namespace App\Controller;

use App\Entity\Tache;
use Symfony\Component\HttpFoundation\Request;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TacheController extends AbstractController
{
    #[Route('/taches', name: 'taches_list')]
    public function index(TacheRepository $tacheRepository): Response
    {
        $taches = $tacheRepository->getTacheListQB();
        $tache = new Tache(); // Create a new Tache for the form
        $form = $this->createForm(TacheType::class, $tache);

        return $this->render('tache/list.html.twig', [
            'taches' => $taches,
            'form' => $form->createView(), // Pass the form to the template
        ]);
    }

    #[Route('/taches/ajouter', name: 'taches_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, TacheRepository $tacheRepository): Response
    {
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir la date de création à la date actuelle
            $tache->setCreatedAt(new \DateTime());

            // Sauvegarde la tâche dans la base de données
            $entityManager->persist($tache);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été ajoutée avec succès !');

            return $this->redirectToRoute('taches_list');
        }

        // If form is not valid, return to the list with errors
        $taches = $tacheRepository->getTacheListQB();
        return $this->render('tache/list.html.twig', [
            'taches' => $taches,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/taches/supprimer/{id_tache}', name: 'taches_delete', methods: ['POST'])]
    public function deleteTache(
        int $id_tache,
        TacheRepository $tacheRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Explicitly find the Tache entity
        $tache = $tacheRepository->find($id_tache);

        if (!$tache) {
            $this->addFlash('error', 'Tâche non trouvée');
            return $this->redirectToRoute('taches_list');
        }

        try {
            $entityManager->remove($tache);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression de la tâche : ' . $e->getMessage());
        }

        return $this->redirectToRoute('taches_list');
    }
}
