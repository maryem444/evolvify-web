<?php
// src/Controller/SparklineController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SparklineController extends AbstractController
{
    #[Route("/sparkline", name:"app_sparkline")]
    public function index()
    {
        return $this->render('sparkline.html.twig');
    }
}
