<?php

declare(strict_types=1);

namespace App\Menu;

use App\Service\LegacyUrlGenerator;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminMenuBuilder
{
    private ?array $navigationConfig = null;

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly LegacyUrlGenerator $legacyUrlGenerator,
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

            $menu->addChild($label, [
                'uri' => $this->resolveMenuUri($routeName, $params),
            ])->setAttribute('class', $item['class'] ?? '');
        }

        return $menu;
    }

    private function resolveMenuUri(string $routeName, array $params): string
    {
        if ($routeName === 'admin/default') {
            $controller = $params['controller'] ?? null;
            $action = $params['action'] ?? 'browse';
            if ($controller && $action === 'browse') {
                return $this->router->generate('app_admin_legacy_browse', [
                    'controller' => $controller,
                ]);
            }
        }

        if ($routeName === 'admin/site') {
            return $this->router->generate('app_admin_legacy_browse', [
                'controller' => 'site',
            ]);
        }

        return $this->legacyUrlGenerator->generate($routeName, $params);
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
