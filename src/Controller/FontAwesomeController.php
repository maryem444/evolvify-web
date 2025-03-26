<?php
// src/Controller/FontAwesomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FontAwesomeController extends AbstractController
{
    #[Route("/font-awesome", name:"app_fontawesome")]
    public function index()
    {
        return $this->render('font_awesome.html.twig');
    }
}
