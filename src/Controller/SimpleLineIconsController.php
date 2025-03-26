<?php
// src/Controller/SimpleLineIconsController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SimpleLineIconsController extends AbstractController
{
    #[Route("/simple-line-icons", name:"app_simplelineicons")]
    public function index()
    {
        return $this->render('simple_line_icons.html.twig');
    }
}
