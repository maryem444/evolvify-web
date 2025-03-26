<?php
// src/Controller/SweetAlertController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SweetAlertController extends AbstractController
{
    #[Route("/sweet-alert", name:"app_sweetalert")]
    public function index()
    {
        return $this->render('sweet_alert.html.twig');
    }
}
