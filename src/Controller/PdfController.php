<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ProjetRepository;

class PdfController extends AbstractController
{
  #[Route('/export/pdf', name: 'export_pdf')]
  public function exportPdf(ProjetRepository $projetRepository): Response
  {
    // Récupérer les projets depuis la base de données
    $projets = $projetRepository->findAll();

    // Configuration de Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $dompdf = new Dompdf($options);

    // Générer le HTML du PDF avec Twig
    $html = $this->renderView('projets/pdf.html.twig', [
      'projets' => $projets,
      'dateExport' => new \DateTime(),
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Retourner le PDF en réponse
    return new Response(
      $dompdf->output(),
      Response::HTTP_OK,
      [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="liste_projets.pdf"'
      ]
    );
  }
}
