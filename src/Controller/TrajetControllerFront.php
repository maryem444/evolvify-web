<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Entity\Abonnement;
use App\Entity\MoyenTransport;
use App\Entity\StatusTrajet;
use App\Entity\StatusTransport;
use App\Repository\TrajetRepository;
use App\Repository\AbonnementRepository;
use App\Repository\MoyenTransportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

final class TrajetControllerFront extends AbstractController
{
    #[Route('/trajet_front', name: 'trajet_list_front')]
    public function listTrajets(TrajetRepository $trajetRepository, MoyenTransportRepository $moyenTransportRepository): Response
    {
        $trajets = $trajetRepository->findAll();
        $moyensTransport = $moyenTransportRepository->findAll();
         // Calcul des statistiques
    $totalTrajets = count($trajets);
    $totalDistance = array_sum(array_map(fn($trajet) => $trajet->getDistance(), $trajets));
    $totalDuration = array_sum(array_map(fn($trajet) => $trajet->getDurée_estimé(), $trajets));

        return $this->render('trajet/list_employe.html.twig', [
            'trajets' => $trajets,
            'moyentransport' => $moyensTransport,
            'totalTrajets' => $totalTrajets,
            'totalDistance' => $totalDistance,
            'totalDuration' => $totalDuration,
        ]);
    }

    #[Route('/trajet/new', name: 'trajet_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AbonnementRepository $abonnementRepository, MoyenTransportRepository $moyenTransportRepository): JsonResponse
    {
        try {
            $point_dep = $request->request->get('point_dep');
            $point_arr = $request->request->get('point_arr');
            $distance = $request->request->get('distance');
            $durée_estimé = $request->request->get('durée_estimé');
            $id_employe = $request->request->getInt('id_employe');
            $statusValue = $request->request->get('status');
            $id_moyen = $request->request->getInt('id_moyen');
            
            // Debug log
            error_log("Received data: " . json_encode([
                'point_dep' => $point_dep,
                'point_arr' => $point_arr,
                'distance' => $distance,
                'durée_estimé' => $durée_estimé,
                'id_employe' => $id_employe,
                'status' => $statusValue,
                'id_moyen' => $id_moyen,
            ]));

            // Validation des champs obligatoires
            if (!$point_dep || !$point_arr || !$distance || !$durée_estimé || !$id_employe || !$statusValue) {
                return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
            }

            // Vérification de l'existence de l'abonnement actif pour l'employé
            $abonnement = $abonnementRepository->findOneBy(['id_employe' => $id_employe, 'status' => 'ACTIF']);
            if (!$abonnement) {
                return new JsonResponse(['error' => 'L\'employé n\'est pas abonné ou son abonnement n\'est pas actif'], Response::HTTP_BAD_REQUEST);
            }

            // Vérification de l'existence du moyen de transport
            $moyenTransport = $moyenTransportRepository->find($id_moyen);
            if (!$moyenTransport) {
                return new JsonResponse(['error' => 'Moyen de transport introuvable'], Response::HTTP_BAD_REQUEST);
            }
            
            // Vérification de la disponibilité du moyen de transport
            if ($moyenTransport->getStatus() !== StatusTransport::DISPONIBLE) {
                return new JsonResponse(['error' => 'Moyen de transport non disponible'], Response::HTTP_BAD_REQUEST);
            }

            // Création du nouvel objet Trajet
            $trajet = new Trajet();
            $trajet->setPoint_dep($point_dep);
            $trajet->setPoint_arr($point_arr);
            $trajet->setDistance((float) $distance);
            
            $date = \DateTime::createFromFormat('H:i:s', $durée_estimé);

            if (!$date) {
                // Essayer le format HTML "time" (HH:MM)
                $date = \DateTime::createFromFormat('H:i', $durée_estimé);
                if ($date) {
                    // Ajouter les secondes à 00
                    $durée_estimé = $date->format('H:i') . ':00';
                    $date = \DateTime::createFromFormat('H:i:s', $durée_estimé);
                }
            }
            
            if (!$date) {
                return new JsonResponse([
                    'error' => 'Le format de la durée estimée est invalide. Utilisez HH:MM ou HH:MM:SS.'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $trajet->setDurée_estimé($date);
            
            
            $trajet->setId_employe($id_employe);
            
            try {
                $trajet->setStatus(StatusTrajet::from($statusValue));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Status de trajet invalide: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            
            $trajet->setMoyenTransport($moyenTransport);

            // Persistance de l'entité Trajet
            $entityManager->persist($trajet);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Trajet ajouté avec succès'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            error_log('Error in trajet_new: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Une erreur est survenue: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/trajet/delete/{id}', name: 'trajet_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager, TrajetRepository $repository, int $id): JsonResponse
    {
        try {
            $trajet = $repository->find($id);
            if (!$trajet) {
                return new JsonResponse(['error' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
            }

            $entityManager->remove($trajet);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Trajet supprimé avec succès']);
        } catch (\Exception $e) {
            error_log('Error in trajet_delete: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la suppression: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/trajet/{id}', name: 'trajet_edit', methods: ['GET'])]
    public function editForm(int $id, TrajetRepository $repository, MoyenTransportRepository $moyenTransportRepository): JsonResponse
    {
        try {
            $trajet = $repository->find($id);
            if (!$trajet) {
                return new JsonResponse(['error' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Récupérer tous les moyens de transport disponibles
            $moyensTransport = $moyenTransportRepository->findBy(['status' => StatusTransport::DISPONIBLE]);

            return new JsonResponse([
                'id_T' => $trajet->getId_T(),
                'point_dep' => $trajet->getPoint_dep(),
                'point_arr' => $trajet->getPoint_arr(),
                'distance' => $trajet->getDistance(),
                'durée_estimé' => $trajet->getDurée_estimé()->format('H:i:s'),
                'id_employe' => $trajet->getId_employe(),
                'status' => $trajet->getStatus()->value,
                'id_moyen' => $trajet->getMoyenTransport()->getId_moyen(),
                'type_moyen' => $trajet->getMoyenTransport()->getType_moyen(),
                'moyensTransport' => array_map(function ($moyen) {
                    return [
                        'id' => $moyen->getId_moyen(),
                        'type_moyen' => $moyen->getType_moyen(),
                        'immatriculation' => $moyen->getImmatriculation(),
                    ];
                }, $moyensTransport),
            ]);
        } catch (\Exception $e) {
            error_log('Error in trajet_edit: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la récupération: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/trajet/edit/{id}', name: 'trajet_update', methods: ['POST'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, TrajetRepository $repository, AbonnementRepository $abonnementRepository, MoyenTransportRepository $moyenTransportRepository): JsonResponse
    {
        try {
            $trajet = $repository->find($id);
            if (!$trajet) {
                return new JsonResponse(['error' => 'Trajet introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Récupération des nouvelles données
            $point_dep = $request->request->get('point_dep');
            $point_arr = $request->request->get('point_arr');
            $distance = $request->request->get('distance');
            $durée_estimé = $request->request->get('durée_estimé');
            $id_employe = $request->request->getInt('id_employe');
            $statusValue = $request->request->get('status');
            $id_moyen = $request->request->getInt('id_moyen');

            // Validation des champs obligatoires
            if (!$point_dep || !$point_arr || !$distance || !$durée_estimé || !$id_employe || !$statusValue || !$id_moyen) {
                return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
            }

            // Vérification de l'abonnement actif de l'employé
            $abonnement = $abonnementRepository->findOneBy(['id_employe' => $id_employe, 'status' => 'ACTIF']);
            if (!$abonnement) {
                return new JsonResponse(['error' => 'L\'employé n\'est pas abonné ou son abonnement n\'est pas actif'], Response::HTTP_BAD_REQUEST);
            }

            // Vérification du moyen de transport
            $moyenTransport = $moyenTransportRepository->find($id_moyen);
            if (!$moyenTransport) {
                return new JsonResponse(['error' => 'Moyen de transport non trouvé'], Response::HTTP_BAD_REQUEST);
            }
            
            // Si le moyen de transport a changé et que le nouveau n'est pas disponible
            $currentMoyenTransport = $trajet->getMoyenTransport();
            if ($currentMoyenTransport->getId_moyen() !== $moyenTransport->getId_moyen() && 
                $moyenTransport->getStatus() !== StatusTransport::DISPONIBLE) {
                return new JsonResponse(['error' => 'Moyen de transport non disponible'], Response::HTTP_BAD_REQUEST);
            }

            // Mise à jour du trajet
            $trajet->setPoint_dep($point_dep);
            $trajet->setPoint_arr($point_arr);
            $trajet->setDistance((float) $distance);
            
            try {
                $trajet->setDurée_estimé(new \DateTime($durée_estimé));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Format de durée invalide: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            
            $trajet->setId_employe($id_employe);
            
            try {
                $trajet->setStatus(StatusTrajet::from($statusValue));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Status de trajet invalide: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            
            $trajet->setMoyenTransport($moyenTransport);

            // Enregistrement de la mise à jour
            $entityManager->flush();

            return new JsonResponse(['message' => 'Trajet mis à jour avec succès']);
        } catch (\Exception $e) {
            error_log('Error in trajet_update: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}