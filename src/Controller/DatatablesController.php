<?php
// src/Controller/DatatablesController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DatatablesController extends AbstractController
{
    #[Route("/datatables", name:"app_datatables")]
    public function index()
    {
        return $this->render('datatables.html.twig');
    }
}
