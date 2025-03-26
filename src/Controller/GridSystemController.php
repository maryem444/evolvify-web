<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GridSystemController extends AbstractController
{

    #[Route("/grid-system", name:"app_gridsystem")]

    public function index()
    {
        return $this->render('grid_system.html.twig');
    }
}
