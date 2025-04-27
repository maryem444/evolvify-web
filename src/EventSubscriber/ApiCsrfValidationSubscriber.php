<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ApiCsrfValidationSubscriber implements EventSubscriberInterface
{
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        
        // Liste des routes à exempter de la validation CSRF
        $exemptRoutes = [
            '/facial-enrollment/save',
            // Ajoutez d'autres routes au besoin
        ];
        
        // Si la route actuelle est dans la liste des routes exemptées
        if (in_array($request->getPathInfo(), $exemptRoutes)) {
            // Désactiver temporairement la validation CSRF pour cette requête
            $request->attributes->set('_csrf_token_id', false);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10], // Priorité élevée
        ];
    }
}