<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Catches corrupt session data caused by Laminas session objects being
 * stored in the PHP session. When Symfony tries to deserialize them,
 * it can fail with UnexpectedValueException. This listener invalidates
 * the session so the request can proceed cleanly.
 */
final class SessionSanitizer implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // Run very early, before security firewall (priority 8)
        return [KernelEvents::REQUEST => ['onKernelRequest', 200]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        try {
            // Trigger session start to check for corrupt data
            $request->getSession()->get('_sf2_attributes');
        } catch (\Throwable $e) {
            $this->logger->warning('[SessionSanitizer] Corrupt session data detected, invalidating session: {message}', [
                'message' => $e->getMessage(),
            ]);

            // Clear the corrupt session
            try {
                $request->getSession()->invalidate();
            } catch (\Throwable) {
                // If even invalidation fails, destroy at the PHP level
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_unset();
                    session_destroy();
                }
                // Remove the session cookie so the client starts fresh
                $cookieName = session_name();
                if (isset($_COOKIE[$cookieName])) {
                    setcookie($cookieName, '', time() - 3600, '/');
                    unset($_COOKIE[$cookieName]);
                }
            }
        }
    }
}
