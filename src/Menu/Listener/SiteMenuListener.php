<?php

declare(strict_types=1);

namespace App\Menu\Listener;

use App\Menu\Event\SiteMenuEvent;
use App\Service\LegacyUrlGenerator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SiteMenuEvent::class)]
final class SiteMenuListener
{
    public function __construct(private readonly LegacyUrlGenerator $legacyUrlGenerator)
    {
    }

    public function __invoke(SiteMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $site = $event->getSite();
        $slug = $site->getSlug();

        $menu->addChild('Site admin', [
            'uri' => $this->legacyUrlGenerator->generate('admin/site/slug', ['site-slug' => $slug]),
        ]);

        $menu->addChild('Pages', [
            'uri' => $this->legacyUrlGenerator->generate('admin/site/slug/page', ['site-slug' => $slug]),
        ]);

        $menu->addChild('Navigation', [
            'uri' => $this->legacyUrlGenerator->generate('admin/site/slug/action', [
                'site-slug' => $slug,
                'action' => 'navigation',
            ]),
        ]);

        $menu->addChild('Resources', [
            'uri' => $this->legacyUrlGenerator->generate('admin/site/slug/action', [
                'site-slug' => $slug,
                'action' => 'resources',
            ]),
        ]);

        $menu->addChild('User permissions', [
            'uri' => $this->legacyUrlGenerator->generate('admin/site/slug/action', [
                'site-slug' => $slug,
                'action' => 'users',
            ]),
        ]);

        $menu->addChild('Theme', [
            'uri' => $this->legacyUrlGenerator->generate('admin/site/slug/action', [
                'site-slug' => $slug,
                'action' => 'theme',
            ]),
        ]);
    }
}
