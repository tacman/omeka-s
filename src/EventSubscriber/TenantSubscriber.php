<?php

namespace App\EventSubscriber;

use App\Tenant\TenantContext;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        #[Autowire('%app.tenant_base_domain%')]
        private readonly string $baseDomain = '',
    ) {
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
        $tenantCode = $this->resolveTenantCode($request->getHost());
        $this->tenantContext->setTenantCode($tenantCode);
        $request->attributes->set('tenant_code', $tenantCode);
    }

    private function resolveTenantCode(string $host): ?string
    {
        $host = strtolower(trim($host));
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $baseDomain = strtolower(trim($this->baseDomain));
        $baseDomain = trim($baseDomain, '.');

        if ($baseDomain !== '') {
            if ($host === $baseDomain) {
                return null;
            }
            if (!str_ends_with($host, '.' . $baseDomain)) {
                return null;
            }
            $remaining = substr($host, 0, -strlen('.' . $baseDomain));
            $remaining = trim($remaining, '.');
            if ($remaining === '') {
                return null;
            }
            $parts = explode('.', $remaining);
            $candidate = $parts[0] ?? '';
        } else {
            $parts = explode('.', $host);
            if (count($parts) < 2) {
                return null;
            }

            $last = $parts[count($parts) - 1];
            if ($last === 'localhost') {
                return $parts[0] !== 'localhost' ? $parts[0] : null;
            }

            if (count($parts) < 3) {
                return null;
            }

            $candidate = $parts[0];
        }

        if ($candidate === '' || $candidate === 'www') {
            return null;
        }

        return $candidate;
    }
}
