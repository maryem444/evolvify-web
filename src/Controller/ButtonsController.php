<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ButtonsController extends AbstractController
{
    #[Route('/buttons', name: 'app_buttons')]
    public function index()
    {
        return $this->render('buttons.html.twig');
    }
}
