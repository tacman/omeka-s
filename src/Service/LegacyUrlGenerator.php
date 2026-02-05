<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LegacyUrlGenerator
{
    public function __construct(private readonly UrlGeneratorInterface $router)
    {
    }

    public function generate(string $routeName, array $params = []): string
    {
        $base = $this->router->generate('app_legacy_proxy', [
            'routeName' => $routeName,
        ]);

        if ($params === []) {
            return $base;
        }

        return $base . '?' . http_build_query($params);
    }
}
