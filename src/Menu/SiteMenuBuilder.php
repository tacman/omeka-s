<?php

declare(strict_types=1);

namespace App\Menu;

use App\Menu\Event\SiteMenuEvent;
use App\Tenant\TenantContext;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Omeka\Entity\Site;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class SiteMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly RequestStack $requestStack,
        private readonly TenantContext $tenantContext,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createSiteMenu(array $options = []): ItemInterface
    {
        $menu = $this->factory->createItem('site_admin');
        $menu->setChildrenAttribute('class', 'site-admin-menu');

        $siteSlug = $this->resolveSiteSlug();
        if (!$siteSlug) {
            return $menu;
        }

        $site = $this->tenantContext->getRepository(Site::class)->findOneBy(['slug' => $siteSlug]);
        if (!$site) {
            return $menu;
        }

        $this->eventDispatcher->dispatch(new SiteMenuEvent($menu, $site));

        return $menu;
    }

    private function resolveSiteSlug(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $slug = $request->attributes->get('siteSlug')
            ?? $request->attributes->get('site-slug')
            ?? $request->query->get('site-slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }
}
