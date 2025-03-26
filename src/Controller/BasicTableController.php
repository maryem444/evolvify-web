<?php
// src/Controller/BasicTableController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class BasicTableController extends AbstractController
{
    #[Route("/basic-table", name:"app_basictable")]
    public function index()
    {
        return $this->render('basic_table.html.twig');
    }
}
