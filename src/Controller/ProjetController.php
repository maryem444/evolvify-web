<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use App\Form\ProjetFilterType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjetController extends AbstractController
{
    #[Route('/projets', name: 'projets_list')]
    public function listProjets(
        Request $request,
        ProjetRepository $projetRepository,
        PaginatorInterface $paginator
    ): Response {
        // Création du formulaire de filtre
        $filterForm = $this->createForm(ProjetFilterType::class);
        $filterForm->handleRequest($request);

        // Initialisation des filtres
        $filters = [];

        // Si le formulaire est soumis et valide, on récupère les filtres
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $formData = $filterForm->getData();

            if (!empty($formData['name'])) {
                $filters['name'] = $formData['name'];
            }

            if (!empty($formData['abbreviation'])) {
                $filters['abbreviation'] = $formData['abbreviation'];
            }

            if (!empty($formData['status'])) {
                $filters['status'] = $formData['status'];
            }
        }

        // On suppose que searchProjets retourne un QueryBuilder
        $queryBuilder = $projetRepository->searchProjets($filters);

        // Application de la pagination
        $pagination = $paginator->paginate(
            $queryBuilder,                           // Le QueryBuilder
            $request->query->getInt('page', 1),      // Page courante (défaut : 1)
            4                                       // Nombre d’éléments par page
        );

        return $this->render('projets/list.html.twig', [
            'projets' => $pagination,
            'filterForm' => $filterForm->createView(),
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
            // Gestion de l'upload du fichier
            /** @var UploadedFile $file */
            $file = $form->get('uploaded_files')->getData();

            if ($file) {
                $uploadsDirectory = $this->getParameter('uploads_directory');
                $newFilename = uniqid() . '.' . $file->guessExtension();

                try {
                    // Déplacement du fichier vers le dossier de destination
                    $file->move($uploadsDirectory, $newFilename);

                    // Enregistrer le chemin du fichier dans la base de données
                    $projet->setUploadedFiles('uploads/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement du fichier.');
                }
            }

            // Persist le projet dans la base de données
            $manager = $doctrine->getManager();
            $manager->persist($projet);
            $manager->flush();

            $this->addFlash('success', 'Le projet a été ajouté avec succès');

            return $this->redirectToRoute('projets_list');
        }

        return $this->render('projets/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/projetDelete/{id}', name: 'projet_delete', methods: ['POST', 'DELETE'])]


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
