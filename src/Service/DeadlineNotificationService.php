<?php
// src/Service/DeadlineNotificationService.php

namespace App\Service;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

class DeadlineNotificationService
{
    private $projetRepository;
    private $notifier;

    public function __construct(ProjetRepository $projetRepository, NotifierInterface $notifier)
    {
        $this->projetRepository = $projetRepository;
        $this->notifier = $notifier;
    }

    public function checkUpcomingDeadlines()
    {
        $tomorrow = new \DateTimeImmutable('tomorrow');
        $tomorrowFormatted = $tomorrow->format('Y-m-d');
        
        // Récupère tous les projets dont la date de fin est demain
        $projets = $this->projetRepository->findProjectsWithDeadlineTomorrow();
        
        $notifications = [];
        
        foreach ($projets as $projet) {
            $notification = new Notification(
                sprintf('⚠️ Le projet "%s" arrive à échéance demain!', $projet->getName()),
                ['browser']
            );
            
            $notification->content(sprintf(
                'Le projet %s (ref: %s) a sa deadline fixée pour demain %s.',
                $projet->getName(),
                $projet->getAbbreviation(),
                $tomorrowFormatted
            ));
            
            $notifications[] = $notification;
        }
        
        return $notifications;
    }
}