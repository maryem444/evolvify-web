<?php
// src/Controller/JsvectormapController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class JsvectormapController extends AbstractController
{
    #[Route("/jsvectormap", name:"app_jsvectormap")]
    public function index()
    {
        return $this->render('jsvectormap.html.twig');
    }
}
