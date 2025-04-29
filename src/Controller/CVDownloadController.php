<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CVAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CVDownloadController extends AbstractController
{
    #[Route('/download-cv/{id}', name: 'download_cv', methods: ['GET'])]
    public function downloadCv($id, EntityManagerInterface $em, Request $request, CVAnalysisService $cvAnalysisService): BinaryFileResponse
    {
        $utilisateur = $em->getRepository(User::class)->find($id);
    
        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }
    
        $fileName = $utilisateur->getUploadedCv();
        if (!$fileName) {
            throw $this->createNotFoundException('CV non trouvé pour cet utilisateur.');
        }
    
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $fileName;
    
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier CV introuvable sur le serveur.');
        }
        
        // Si le paramètre analyze=1 est présent dans l'URL, on analyse le CV
        if ($request->query->get('analyze')) {
            $resultats = $cvAnalysisService->analyzeCV($filePath);
            
            // Stockez les résultats en session pour les afficher après le téléchargement
            $request->getSession()->set('cv_analysis_results', $resultats);
            
            // Ajoutez un paramètre à la réponse pour déclencher l'affichage des résultats
            $response = new BinaryFileResponse($filePath);
            $response->headers->set('X-Analyze-CV', '1');
        } else {
            $response = new BinaryFileResponse($filePath);
        }
        
        $response->setContentDisposition('attachment', $fileName);
        $response->headers->set('Content-Type', 'application/pdf');
        
        return $response;
    }
}