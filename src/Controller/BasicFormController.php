<?php
// src/Controller/BasicFormController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class BasicFormController extends AbstractController
{
    #[Route("/basic-form", name:"app_basicform")]
    public function index()
    {
        return $this->render('basic_form.html.twig');
    }
}
