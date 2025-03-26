<?php
// src/Controller/GoogleMapsController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GoogleMapsController extends AbstractController
{
    #[Route("/google-maps", name:"app_googlemaps")]
    public function index()
    {
        return $this->render('google_maps.html.twig');
    }
}
