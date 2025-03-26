<?php
// src/Controller/IconMenuController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IconMenuController extends AbstractController
{
    #[Route("/icon-menu", name:"app_icon_menu")]
    public function index()
    {
        return $this->render('icon_menu.html.twig');
    }
}
