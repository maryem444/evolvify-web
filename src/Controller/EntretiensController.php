<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EntretiensController extends AbstractController
{
    #[Route('/Entretiens', name: 'app_Entretiens')]
    public function index()
    {
        return $this->render('Recrutement/Entretiens.html.twig');
    }
}