<?php
namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\StatusAbonnement;
use App\Repository\AbonnementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

final class AbonnementController extends AbstractController
{
    #[Route('/abonnement', name: 'abonnement_list')]
    public function listAbonnements(AbonnementRepository $abonnementRepository): Response
    {
        $abonnements = $abonnementRepository->findAll();
        return $this->render('abonnement/list.html.twig', [
            'abonnements' => $abonnements,
        ]);
    }

    #[Route('/abonnement/new', name: 'abonnement_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $type_Ab = $request->request->get('type_Ab');
        $date_debut_input = $request->request->get('date_debut');
        // Utiliser la date actuelle si aucune date de début n'est fournie
        $date_debut = $date_debut_input ? new \DateTime($date_debut_input) : new \DateTime();
        
        $date_exp_input = $request->request->get('date_exp');
        $date_exp = $date_exp_input ? new \DateTime($date_exp_input) : null;
        
        $prix = $request->request->get('prix');
        $id_employe = $request->request->get('id_employe');
        $statusValue = $request->request->get('status');

        if (!$type_Ab || !$date_debut || !$date_exp || !$prix || !$id_employe || !$statusValue) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'abonnement
        $abonnement = new Abonnement();
        $abonnement->setType_Ab($type_Ab);
        $abonnement->setDate_debut($date_debut);
        $abonnement->setDate_exp($date_exp);
        $abonnement->setPrix((float)$prix);
        $abonnement->setId_employe((int)$id_employe);
        $abonnement->setStatus(StatusAbonnement::from($statusValue));

        // Persister et enregistrer dans la base de données
        $entityManager->persist($abonnement);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Abonnement ajouté avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/abonnement/delete/{id}', name: 'abonnement_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager, AbonnementRepository $repository, int $id): JsonResponse
    {
        $abonnement = $repository->find($id);
        if (!$abonnement) {
            return new JsonResponse(['error' => 'Abonnement introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Suppression de l'abonnement
        $entityManager->remove($abonnement);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Abonnement supprimé avec succès']);
    }

    #[Route('/abonnement/{id}', name: 'abonnement_edit', methods: ['GET'])]
    public function editForm(int $id, AbonnementRepository $repository): JsonResponse
    {
        $abonnement = $repository->find($id);
        if (!$abonnement) {
            return new JsonResponse(['error' => 'Abonnement introuvable'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id_Ab' => $abonnement->getId_Ab(),
            'type_Ab' => $abonnement->getType_Ab(),
            'date_debut' => $abonnement->getDate_debut()->format('Y-m-d'),
            'date_exp' => $abonnement->getDate_exp()->format('Y-m-d'),
            'prix' => $abonnement->getPrix(),
            'id_employe' => $abonnement->getId_employe(),
            'status' => $abonnement->getStatus()->value,
        ]);
    }

    #[Route('/abonnement/edit/{id}', name: 'abonnement_update', methods: ['POST'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, AbonnementRepository $repository): JsonResponse
    {
        $abonnement = $repository->find($id);
        if (!$abonnement) {
            return new JsonResponse(['error' => 'Abonnement introuvable'], Response::HTTP_NOT_FOUND);
        }

        $type_Ab = $request->request->get('type_Ab');
        $date_debut_input = $request->request->get('date_debut');
        $date_debut = $date_debut_input ? new \DateTime($date_debut_input) : new \DateTime();

        $date_exp_input = $request->request->get('date_exp');
        $date_exp = $date_exp_input ? new \DateTime($date_exp_input) : null;
        
        $prix = $request->request->get('prix');
        $id_employe = $request->request->get('id_employe');
        $statusValue = $request->request->get('status');

        if (!$type_Ab || !$date_debut || !$date_exp || !$prix || !$id_employe || !$statusValue) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $abonnement->setType_Ab($type_Ab);
        $abonnement->setDate_debut($date_debut);
        $abonnement->setDate_exp($date_exp);
        $abonnement->setPrix((float)$prix);
        $abonnement->setId_employe((int)$id_employe);
        $abonnement->setStatus(StatusAbonnement::from($statusValue));

        $entityManager->flush();

        return new JsonResponse(['message' => 'Abonnement mis à jour avec succès']);
    }
}
