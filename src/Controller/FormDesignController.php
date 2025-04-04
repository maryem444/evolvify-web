<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FormDesignController extends AbstractController
{
    #[Route('/FormDesign', name: 'app_FormDesign')]
    public function index(): Response
    {
        return $this->render('FormDesign.html.twig');
    }
}
