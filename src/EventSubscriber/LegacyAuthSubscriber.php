<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\LegacyOmekaAuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LegacyAuthSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LegacyOmekaAuthService $authService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/admin') && !str_starts_with($path, '/api') && !str_starts_with($path, '/symfony')) {
            return;
        }

        $this->authService->ensureIdentity();
    }
}
