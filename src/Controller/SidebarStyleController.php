<?php
// src/Controller/SidebarStyleController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SidebarStyleController extends AbstractController
{
    #[Route("/sidebar-style2", name:"app_sidebar_style2")]
    public function index()
    {
        return $this->render('sidebar_style2.html.twig');
    }
}
