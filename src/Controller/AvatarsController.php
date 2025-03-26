<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AvatarsController extends AbstractController
{
    #[Route('/avatars', name: 'app_avatars')]
    public function index()
    {
        // Your logic to handle the avatars route
        return $this->render('avatars.html.twig');
    }
}
