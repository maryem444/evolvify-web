<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CardsController extends AbstractController
{
  #[Route('/projets/cards', name: 'projets_cards')]
  public function listProjetsCards(ProjetRepository $projetRepository): Response
  {
    $projets = $projetRepository->getProjetListQB();

    return $this->render('projets/cards.html.twig', [
      'projets' => $projets
    ]);
  }

  #[Route('/projets/cards/add', name: 'projet_add_card')]
  public function addProjet(Request $request, ManagerRegistry $doctrine): Response
  {
    $projet = new Projet();
    $projet->setStatus(StatutProjet::IN_PROGRESS);
    $form = $this->createForm(ProjetType::class, $projet);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $manager = $doctrine->getManager();
      $manager->persist($projet);
      $manager->flush();

      $this->addFlash('success', 'Le projet a été ajouté avec succès');

      // Rediriger explicitement vers la vue en cartes
      return $this->redirectToRoute('projets_cards');
    }

    return $this->render('projets/add.html.twig', [
      'form' => $form->createView(),
      'redirect_route' => 'projets_cards' // Passer la route de redirection au template
    ]);
  }

  #[Route('/projets/cards/delete/{id}', name: 'projet_delete_card', methods: ['POST', 'DELETE'])]
  public function deleteProjet(Projet $projet, ManagerRegistry $doctrine): Response
  {
    $manager = $doctrine->getManager();
    $manager->remove($projet);
    $manager->flush();

    $this->addFlash('success', 'Le projet a été supprimé avec succès');

    // Rediriger explicitement vers la vue en cartes
    return $this->redirectToRoute('projets_cards');
  }

  #[Route('/projets/cards/{id}/edit', name: 'projet_edit_card')]
  public function edit(Request $request, Projet $projet, EntityManagerInterface $entityManager): Response
  {
    $oldFilePath = $projet->getUploadedFiles(); // Stockez le chemin de l'ancien fichier

    $form = $this->createForm(ProjetType::class, $projet);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      /** @var UploadedFile $file */
      $file = $form->get('uploaded_files')->getData();

      if ($file) {
        $uploadsDirectory = $this->getParameter('uploads_directory');
        $newFilename = uniqid() . '.' . $file->guessExtension();

        try {
          // Supprimer l'ancien fichier s'il existe
          if ($oldFilePath) {
            $fullOldPath = $this->getParameter('kernel.project_dir') . '/public/' . $oldFilePath;
            if (file_exists($fullOldPath)) {
              unlink($fullOldPath);
            }
          }

          $file->move($uploadsDirectory, $newFilename);
          $projet->setUploadedFiles('uploads/' . $newFilename); // Stocker le nouveau chemin
        } catch (FileException $e) {
          $this->addFlash('danger', 'Erreur lors du téléchargement du fichier.');
        }
      }

      // Sauvegarder les modifications en base de données - LIGNE MANQUANTE AJOUTÉE
      $entityManager->flush();

      $this->addFlash('success', 'Projet modifié avec succès !');

      // Rediriger explicitement vers la vue en cartes
      return $this->redirectToRoute('projets_cards');
    }

    return $this->render('projets/edit.html.twig', [
      'form' => $form->createView(),
      'projet' => $projet,
      'redirect_route' => 'projets_cards' // Passer la route de redirection au template
    ]);
  }
  #[Route('/projets/cards/{id}/details', name: 'projet_details_card')]
  public function details(Projet $projet): Response
  {
    return $this->render('projets/details.html.twig', [
      'projet' => $projet
    ]);
  }
}
