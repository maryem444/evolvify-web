<?php
// src/EventSubscriber/PasswordResetSecuritySubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;


class PasswordResetSecuritySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Only process main requests (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $session = $request->getSession();

        // Apply only to password reset paths
        if (
            str_contains($path, '/forgot-password') ||
            str_contains($path, '/verify-code') ||
            str_contains($path, '/reset-password')
        ) {

            $response = $event->getResponse();

            // Add cache control headers to prevent browser caching
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

            // Add headers to prevent back/forward navigation
            $response->headers->set('X-Frame-Options', 'DENY');

            // Add a CSP header to prevent caching
            $response->headers->set('Content-Security-Policy', "no-store");

            // If we're on a sensitive page and the user has completed the reset
            if (
                $session->has('password_reset_completed') &&
                !str_contains($path, '/login')
            ) {
                // Clear any stored history and redirect to login
                // This helps ensure users can't go back to sensitive pages
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->add('info', 'Votre mot de passe a été réinitialisé. Veuillez vous connecter.');

                $newResponse = new Response('', Response::HTTP_FOUND);
                $newResponse->headers->set('Location', '/login');
                $event->setResponse($newResponse);
            }
        }
    }
}
