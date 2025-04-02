<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CandidatesController extends AbstractController
{
    #[Route('/Candidates', name: 'app_Candidates')]
    public function index()
    {
        return $this->render('Recrutement/Candidates.html.twig');
    }
}