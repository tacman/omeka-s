<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteStackInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class LaminasRouterService
{
    private ?RouteStackInterface $router = null;
    private ?array $routesConfig = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function getRouter(): RouteStackInterface
    {
        if ($this->router) {
            return $this->router;
        }

        $routerConfig = $this->getRouterConfig();

        $this->router = TreeRouteStack::factory($routerConfig);

        return $this->router;
    }

    public function url(string $name, array $params = [], array $options = []): string
    {
        return $this->getRouter()->assemble($params, ['name' => $name] + $options);
    }

    public function getRouteTable(): array
    {
        $routes = $this->getRoutesConfig();
        $rows = [];
        $this->flattenRoutes($routes, '', $rows);

        return $rows;
    }

    private function getRouterConfig(): array
    {
        $config = $this->getRawConfig();

        return $config['router'] ?? [];
    }

    private function getRoutesConfig(): array
    {
        $routerConfig = $this->getRouterConfig();

        return $routerConfig['routes'] ?? [];
    }

    private function getRawConfig(): array
    {
        if ($this->routesConfig !== null) {
            return $this->routesConfig;
        }

        $configPath = $this->projectDir . '/application/config/routes.config.php';
        if (!is_file($configPath)) {
            throw new RuntimeException(sprintf('Missing Omeka routes config: %s', $configPath));
        }

        $this->routesConfig = require $configPath;

        return $this->routesConfig;
    }

    private function flattenRoutes(array $routes, string $prefix, array &$rows): void
    {
        foreach ($routes as $name => $route) {
            $fullName = $prefix !== '' ? $prefix . '/' . $name : $name;
            $options = $route['options'] ?? [];
            $routePath = $options['route'] ?? '';
            $type = $route['type'] ?? '';
            $defaults = $options['defaults'] ?? [];
            $constraints = $options['constraints'] ?? [];

            $rows[] = [
                'name' => $fullName,
                'route' => $routePath,
                'type' => $this->formatRouteType($type),
                'defaults' => $defaults,
                'constraints' => $constraints,
                'may_terminate' => (bool) ($route['may_terminate'] ?? false),
            ];

            $childRoutes = $route['child_routes'] ?? [];
            if ($childRoutes) {
                $this->flattenRoutes($childRoutes, $fullName, $rows);
            }
        }
    }

    private function formatRouteType(string $type): string
    {
        if ($type === '') {
            return '';
        }

        if (str_contains($type, '\\')) {
            $parts = explode('\\', $type);
            return end($parts) ?: $type;
        }

        return $type;
    }
}
