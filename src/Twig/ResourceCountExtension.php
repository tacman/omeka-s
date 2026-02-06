<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\OmekaApiService;
use Symfony\Bridge\Twig\Attribute\AsTwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[AsTwigExtension]
final class ResourceCountExtension extends AbstractExtension
{
    public function __construct(private readonly OmekaApiService $api)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('resource_count', $this->resourceCount(...)),
        ];
    }

    /**
     * Get the total count of a resource type, optionally filtered.
     *
     * Usage:
     *   {{ resource_count('site_pages', {site_id: site.id()}) }}
     *   {{ resource_count('items') }}
     */
    public function resourceCount(string $resource, array $query = []): ?int
    {
        try {
            $query['limit'] = 0;
            return $this->api->search($resource, $query)->getTotalResults();
        } catch (\Throwable) {
            return null;
        }
    }
}
