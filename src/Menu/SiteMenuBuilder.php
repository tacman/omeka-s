<?php

declare(strict_types=1);

namespace App\Menu;

use App\Service\OmekaApiService;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SiteMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $router,
        private readonly OmekaApiService $api,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createSiteMenu(array $options = []): ItemInterface
    {
        $menu = $this->factory->createItem('site_admin');
        $menu->setChildrenAttribute('class', 'navigation');

        $slug = $this->resolveSiteSlug();
        if (!$slug) {
            return $menu;
        }

        $site = $this->findSite($slug);
        if (!$site) {
            return $menu;
        }

        $siteId = $site->id();
        $params = ['slug' => $slug];

        $edit = $menu->addChild('Site admin', [
            'uri' => $this->router->generate('app_admin_site_edit', $params),
        ]);
        $edit->setAttribute('class', 'site-info');

        $pages = $menu->addChild('Pages', [
            'uri' => $this->router->generate('app_admin_site_pages', $params),
        ]);
        $pages->setAttribute('class', 'pages');
        $pages->setExtra('badge', $this->count('site_pages', ['site_id' => $siteId]));

        $nav = $menu->addChild('Navigation', [
            'uri' => $this->router->generate('app_admin_site_navigation', $params),
        ]);
        $nav->setAttribute('class', 'navigation');

        $resources = $menu->addChild('Resources', [
            'uri' => $this->router->generate('app_admin_site_resources', $params),
        ]);
        $resources->setAttribute('class', 'resources');
        $resources->setExtra('badge', $this->count('items', ['site_id' => $siteId]));

        $users = $menu->addChild('User permissions', [
            'uri' => $this->router->generate('app_admin_site_users', $params),
        ]);
        $users->setAttribute('class', 'users');

        $theme = $menu->addChild('Theme', [
            'uri' => $this->router->generate('app_admin_site_theme', $params),
        ]);
        $theme->setAttribute('class', 'theme');

        return $menu;
    }

    private function resolveSiteSlug(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        // The Symfony site admin routes use {slug} as the parameter name
        $slug = $request->attributes->get('slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    private function findSite(string $slug): mixed
    {
        try {
            $sites = $this->api->search('sites', ['slug' => $slug])->getContent();
            return $sites[0] ?? null;
        } catch (\Throwable $e) {
            $this->logger->debug('[SiteMenuBuilder] Failed to find site {slug}: {message}', [
                'slug' => $slug,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function count(string $resource, array $query): ?int
    {
        try {
            $query['limit'] = 0;
            return $this->api->search($resource, $query)->getTotalResults();
        } catch (\Throwable) {
            return null;
        }
    }
}
