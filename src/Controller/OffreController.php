<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OffreController extends AbstractController
{
    #[Route('/Offre', name: 'app_Offre')]
    public function index()
    {
        return $this->render('Recrutement/Offre.html.twig');
    }
}