<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\LaminasRouterService;
use App\Service\LegacyUrlGenerator;
use Symfony\Bridge\Twig\Attribute\AsTwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[AsTwigExtension]
final class LaminasRouterExtension extends AbstractExtension
{
    public function __construct(
        private readonly LaminasRouterService $laminasRouter,
        private readonly LegacyUrlGenerator $legacyUrlGenerator,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('laminas_url', [$this, 'url']),
            new TwigFunction('legacy_url', [$this, 'legacyUrl']),
            new TwigFunction('laminas_routes', [$this, 'routes']),
            new TwigFunction('laminas_route_table', [$this, 'routes']),
        ];
    }

    public function url(string $name, array $params = [], array $options = []): string
    {
        return $this->laminasRouter->url($name, $params, $options);
    }

    public function routes(): array
    {
        return $this->laminasRouter->getRouteTable();
    }

    public function legacyUrl(string $name, array $params = []): string
    {
        return $this->legacyUrlGenerator->generate($name, $params);
    }
}
