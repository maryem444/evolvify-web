<?php
// src/Controller/NotificationsController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class NotificationsController extends AbstractController
{
    #[Route("/notifications", name:"app_notifications")]
    public function index()
    {
        return $this->render('notifications.html.twig');
    }
}
