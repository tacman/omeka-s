<?php

declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminMenuBuilder
{
    private ?array $navigationConfig = null;

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly UrlGeneratorInterface $router,
        private readonly string $projectDir,
    ) {
    }

    public function createAdminSiteMenu(array $options = []): ItemInterface
    {
        return $this->buildMenu('AdminSite', 'admin_site');
    }

    public function createAdminResourceMenu(array $options = []): ItemInterface
    {
        return $this->buildMenu('AdminResource', 'admin_resource');
    }

    public function createAdminGlobalMenu(array $options = []): ItemInterface
    {
        return $this->buildMenu('AdminGlobal', 'admin_global');
    }

    public function createAdminModuleMenu(array $options = []): ItemInterface
    {
        return $this->buildMenu('AdminModule', 'admin_module');
    }

    private function buildMenu(string $section, string $name): ItemInterface
    {
        $menu = $this->factory->createItem($name);
        $menu->setChildrenAttribute('class', 'admin-menu');

        $items = $this->getNavigationSection($section);
        foreach ($items as $item) {
            $label = $item['label'] ?? null;
            $routeName = $item['route'] ?? null;
            if (!$label || !$routeName) {
                continue;
            }

            $params = [];
            if (isset($item['controller'])) {
                $params['controller'] = $item['controller'];
            }
            if (isset($item['action'])) {
                $params['action'] = $item['action'];
            }

            // Items gets restructured: parent is just a label with Metadata + Media children
            if (($item['controller'] ?? '') === 'item') {
                $menuItem = $menu->addChild($label, [
                    'uri' => $this->resolveMenuUri($routeName, $params),
                ]);
                $menuItem->setAttribute('class', $item['class'] ?? '');

                $menuItem->addChild('Metadata', [
                    'uri' => $this->router->generate('app_admin_item_browse'),
                ])->setAttribute('class', 'items');

                $menuItem->addChild('Media', [
                    'uri' => $this->router->generate('app_admin_media_browse'),
                ])->setAttribute('class', 'media');

                continue;
            }

            $menuItem = $menu->addChild($label, [
                'uri' => $this->resolveMenuUri($routeName, $params),
            ]);
            $menuItem->setAttribute('class', $item['class'] ?? '');

            // Add visible child pages as submenu items
            if (!empty($item['pages'])) {
                $this->addChildPages($menuItem, $item['pages']);
            }
        }

        return $menu;
    }

    private function addChildPages(ItemInterface $parent, array $pages): void
    {
        foreach ($pages as $page) {
            // Only add visible pages
            if (!($page['visible'] ?? false)) {
                continue;
            }

            $label = $page['label'] ?? null;
            $routeName = $page['route'] ?? null;
            if (!$label || !$routeName) {
                continue;
            }

            $params = [];
            if (isset($page['controller'])) {
                $params['controller'] = $page['controller'];
            }
            if (isset($page['action'])) {
                $params['action'] = $page['action'];
            }

            $child = $parent->addChild($label, [
                'uri' => $this->resolveMenuUri($routeName, $params),
            ]);
            $child->setAttribute('class', $page['class'] ?? '');

            // Recursively add children
            if (!empty($page['pages'])) {
                $this->addChildPages($child, $page['pages']);
            }
        }
    }

    private function resolveMenuUri(string $routeName, array $params): string
    {
        // Map legacy routes to Symfony routes
        if ($routeName === 'admin/default') {
            $controller = $params['controller'] ?? null;
            $action = $params['action'] ?? 'browse';

            $symfonyRoute = $this->controllerToSymfonyRoute($controller, $action);
            if ($symfonyRoute) {
                return $this->router->generate($symfonyRoute);
            }
        }

        if ($routeName === 'admin/site') {
            return $this->router->generate('app_admin_site_browse');
        }

        // Fallback: try to generate a Symfony route
        return $this->router->generate('app_admin');
    }

    private function controllerToSymfonyRoute(string $controller, string $action): ?string
    {
        $routeMap = [
            'item' => [
                'browse' => 'app_admin_item_browse',
                'add' => 'app_admin_item_add',
            ],
            'item-set' => [
                'browse' => 'app_admin_item_set_browse',
                'add' => 'app_admin_item_set_add',
            ],
            'media' => [
                'browse' => 'app_admin_media_browse',
            ],
            'vocabulary' => [
                'browse' => 'app_admin_vocabulary_browse',
                'import' => 'app_admin_vocabulary_import',
            ],
            'resource-template' => [
                'browse' => 'app_admin_resource_template_browse',
                'add' => 'app_admin_resource_template_add',
                'import' => 'app_admin_resource_template_import',
            ],
            'user' => [
                'browse' => 'app_admin_user_browse',
                'add' => 'app_admin_user_add',
            ],
            'module' => [
                'browse' => 'app_admin_module_browse',
            ],
            'job' => [
                'browse' => 'app_admin_job_browse',
            ],
            'setting' => [
                'browse' => 'app_admin_setting_browse',
            ],
            'asset' => [
                'browse' => 'app_admin_asset_browse',
                'add' => 'app_admin_asset_add',
            ],
            'site' => [
                'browse' => 'app_admin_site_browse',
                'add' => 'app_admin_site_add',
            ],
        ];

        return $routeMap[$controller][$action] ?? null;
    }

    private function getNavigationSection(string $section): array
    {
        $config = $this->getNavigationConfig();

        return $config['navigation'][$section] ?? [];
    }

    private function getNavigationConfig(): array
    {
        if ($this->navigationConfig !== null) {
            return $this->navigationConfig;
        }

        $configPath = $this->projectDir . '/application/config/navigation.config.php';
        if (!is_file($configPath)) {
            $this->navigationConfig = ['navigation' => []];
            return $this->navigationConfig;
        }

        $this->navigationConfig = require $configPath;

        return $this->navigationConfig;
    }
}
