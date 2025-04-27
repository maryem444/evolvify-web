<?php
namespace App\Controller;

use App\Entity\MoyenTransport;
use App\Entity\StatusTransport;
use App\Repository\MoyenTransportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

final class MoyenTransportController extends AbstractController
{
    #[Route('/moyentransport', name: 'moyentransport_list')]
    public function listMoyens(MoyenTransportRepository $moyenTransportRepository): Response
    {
        // Récupérer tous les moyens de transport
        $moyentransport = $moyenTransportRepository->findAll();
    
        // Calculer les statistiques
        $totalMoyens = count($moyentransport);
        $activeMoyens = count(array_filter($moyentransport, function($transport) {
            // Utiliser "value" directement pour obtenir la valeur de l'énumération
         return $transport->getStatus() === StatusTransport::DISPONIBLE;
}));
$capacities = [];
foreach ($moyentransport as $moyen) {
    $capacities[] = [
        'type' => $moyen->getType_moyen(),
        'capacité' => $moyen->getCapacité()
    ];
}

        // Passer les données à la vue
        return $this->render('moyentransport/list.html.twig', [
            'moyentransport' => $moyentransport,
            'totalMoyens' => $totalMoyens,
            'activeMoyens' => $activeMoyens,
            'capacities' => $capacities
        ]);
    }

    #[Route('/moyentransport/new', name: 'app_moyentransport_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $type_moyen = $request->request->get('type_moyen');
        $capacité = $request->request->getInt('capacité');
        $immatriculation = $request->request->getInt('immatriculation');
        $statusValue = $request->request->get('status');

        if (!$type_moyen || !$capacité || !$immatriculation || !$statusValue) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $transport = new MoyenTransport();
        $transport->setType_moyen($type_moyen);
        $transport->setCapacité($capacité);
        $transport->setImmatriculation($immatriculation);
        $transport->setStatus(StatusTransport::from($statusValue)); // Assurez-vous que StatusTransport::from() fonctionne bien

        $entityManager->persist($transport);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Moyen de transport ajouté avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/moyentransport/delete/{id}', name: 'app_moyentransport_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager, MoyenTransportRepository $repository, int $id): JsonResponse
    {
        $transport = $repository->find($id);
        if (!$transport) {
            return new JsonResponse(['error' => 'Moyen de transport introuvable'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($transport);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Moyen de transport supprimé avec succès']);
    }

    #[Route('/moyentransport/{id}', name: 'moyentransport_edit', methods: ['GET'])]
    public function editForm(int $id, MoyenTransportRepository $repository): JsonResponse
    {
        $transport = $repository->find($id);
        if (!$transport) {
            return new JsonResponse(['error' => 'Moyen de transport introuvable'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id_moyen' => $transport->getId_moyen(),
            'type_moyen' => $transport->getType_moyen(),
            'capacité' => $transport->getCapacité(),
            'immatriculation' => $transport->getImmatriculation(),
            'status' => $transport->getStatus()->value, // Accéder directement à la valeur de l'énumération
        ]);
    }

    #[Route('/moyentransport/edit/{id}', name: 'moyentransport_update', methods: ['POST'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, MoyenTransportRepository $repository): JsonResponse
    {
        $transport = $repository->find($id);
        if (!$transport) {
            return new JsonResponse(['error' => 'Moyen de transport introuvable'], Response::HTTP_NOT_FOUND);
        }

        $type_moyen = $request->request->get('type_moyen');
        $capacité = $request->request->getInt('capacité');
        $immatriculation = $request->request->get('immatriculation');
        $statusValue = $request->request->get('status');

        if (!$type_moyen || !$capacité || !$statusValue) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires sauf immatriculation'], Response::HTTP_BAD_REQUEST);
        }

        $transport->setType_moyen($type_moyen);
        $transport->setCapacité($capacité);
        if ($immatriculation !== null) {
            $transport->setImmatriculation((int) $immatriculation);
        }
        $transport->setStatus(StatusTransport::from($statusValue)); // Assurez-vous que StatusTransport::from() fonctionne bien

        $entityManager->flush();

        return new JsonResponse(['message' => 'Moyen de transport mis à jour avec succès']);
    }

    #[Route('/moyentransport/stats', name: 'moyentransport_stats', methods: ['GET'])]
    public function stats(MoyenTransportRepository $repo): JsonResponse
    {
        $all = $repo->findAll();

        $count = count($all);
        $types = [];
        $status = [];
        $totalCapacity = 0;

        foreach ($all as $m) {
            $type = $m->getType_moyen();
            $statusVal = $m->getStatus()?->value ?? 'Non défini'; // Assurez-vous que status peut être null

            $types[$type] = ($types[$type] ?? 0) + 1;
            $status[$statusVal] = ($status[$statusVal] ?? 0) + 1;
            $totalCapacity += $m->getCapacité();
        }

        $averageCapacity = $count > 0 ? round($totalCapacity / $count, 2) : 0;

        return new JsonResponse([
            'total' => $count,
            'par_type' => $types,
            'par_statut' => $status,
            'capacite_moyenne' => $averageCapacity,
        ]);
    }
}
