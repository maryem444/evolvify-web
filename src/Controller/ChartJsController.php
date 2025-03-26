<?php
// src/Controller/ChartJsController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ChartJsController extends AbstractController
{
    #[Route("/chartjs", name:"app_chartjs")]
    public function index()
    {
        return $this->render('chartjs.html.twig');
    }
}
