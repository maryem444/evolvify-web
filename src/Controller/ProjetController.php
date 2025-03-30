<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjetController extends AbstractController
{
    #[Route('/projets', name: 'projets_list')]
    public function listProjets(ProjetRepository $projetRepository): Response
    {
        $projets = $projetRepository->getProjetListQB(); // Utilisation de la méthode corrigée

        return $this->render('projets/list.html.twig', [
            'projets' => $projets,
        ]);
    }

    #[Route('/projetAdd', name: 'projet_add')]
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

            return $this->redirectToRoute('projets_list'); // Assurez-vous que cette route existe
        }

        return $this->render('projets/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/projetDelete/{id}', name: 'projet_delete', methods: ['POST'])]
    public function deleteProjet(Projet $projet, ManagerRegistry $doctrine): Response
    {
        $manager = $doctrine->getManager();
        $manager->remove($projet);
        $manager->flush();

        $this->addFlash('success', 'Le projet a été supprimé avec succès');

        return $this->redirectToRoute('projets_list');
    }


    #[Route('/projet/{id}/edit', name: 'projet_edit')]
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

            $entityManager->flush();
            return $this->redirectToRoute('projets_list');
        }

        return $this->render('projets/edit.html.twig', [
            'form' => $form->createView(),
            'projet' => $projet,
        ]);
    }
    #[Route('/projet/{id}/taches', name: 'projet_taches')]
    public function showTaches(Projet $projet, TacheRepository $tacheRepository): Response
    {
        // Récupérer les tâches liées à ce projet
        $taches = $tacheRepository->findBy(['projet' => $projet]);
        
        return $this->render('tache/list.html.twig', [
            'projet' => $projet,
            'taches' => $taches
        ]);
    }
   
}
