<?php
// src/Controller/DocumentationController.php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DocumentationController extends AbstractController
{
    #[Route('/documentation', name: 'app_documentation')]
    public function index()
    {
        return $this->render('documentation.html.twig');
    }
}
