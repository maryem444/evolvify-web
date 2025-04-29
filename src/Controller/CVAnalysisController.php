<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Repository\CandidatesRepository;
use App\Service\CVAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class CVAnalysisController extends AbstractController
{
    #[Route('/analyze-cv/{id}', name: 'analyze_cv')]
    public function analyzeCv(int $id,CandidatesRepository $candidateRepository,CVAnalysisService $cvAnalysisService,LoggerInterface $logger): JsonResponse 
    {
        $logger->info('Début analyse CV pour ID candidat : ' . $id);

        $candidate = $candidateRepository->find($id);

        if (!$candidate) {
            $logger->error('Candidat non trouvé pour ID : ' . $id);
            return new JsonResponse(['error' => 'Candidat non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $cvFilename = $candidate->getUploadedCv();

        if (!is_string($cvFilename) || empty($cvFilename)) {
            $logger->error('CV invalide ou manquant pour candidat ID : ' . $id);
            return new JsonResponse(['error' => 'Fichier CV invalide ou manquant.'], Response::HTTP_BAD_REQUEST);
        }

        // Construction du chemin complet
        $cvPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $cvFilename;
        $logger->info('Chemin du CV : ' . $cvPath);

        if (!file_exists($cvPath)) {
            $logger->error('Fichier CV introuvable : ' . $cvPath);
            return new JsonResponse(['error' => 'Fichier CV introuvable.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $cvAnalysisService->analyzeCV($cvPath);

            if (isset($result['error'])) {
                $logger->error('Erreur API Analyse CV : ' . $result['message']);
                return new JsonResponse(['error' => $result['message']], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $logger->info('Analyse CV réussie pour ID candidat : ' . $id);
            return new JsonResponse($result);

        } catch (\Exception $e) {
            $logger->critical('Exception lors de l\'analyse CV : ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur interne: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
