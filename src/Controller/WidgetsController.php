<?php
// src/Controller/WidgetController.php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WidgetsController extends AbstractController
{
    #[Route('/widgets', name: 'app_widgets')]
    public function index()
    {
        return $this->render('widgets.html.twig');
    }
}
