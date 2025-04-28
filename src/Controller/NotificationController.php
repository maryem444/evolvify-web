<?php
// src/Controller/NotificationController.php

namespace App\Controller;

use App\Service\DeadlineNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/check-deadlines', name: 'check_deadlines')]
    public function checkDeadlines(DeadlineNotificationService $service): Response
    {
        $notifications = $service->checkUpcomingDeadlines();

        // Pour le test, on renvoie juste les notifications
        return $this->json([
            'notifications' => array_map(function ($notification) {
                return [
                    'subject' => $notification->getSubject(),
                    'content' => $notification->getContent(),
                ];
            }, $notifications)
        ]);
    }
}
