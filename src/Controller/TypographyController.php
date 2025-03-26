<?php
// src/Controller/TypographyController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TypographyController extends AbstractController
{
    #[Route("/typography", name:"app_typography")]
    public function index()
    {
        return $this->render('typography.html.twig');
    }
}
