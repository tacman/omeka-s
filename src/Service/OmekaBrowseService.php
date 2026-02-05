<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\BrowseResult;
use Omeka\Stdlib\Browse as OmekaBrowse;

final class OmekaBrowseService
{
    private ?OmekaBrowse $browse = null;

    public function __construct(
        private readonly OmekaApiService $apiService,
        private readonly LegacyOmekaApplication $legacyApp,
    ) {
    }

    public function browse(string $resourceType, array $query = []): BrowseResult
    {
        $browse = $this->getBrowseService();
        $defaults = $browse->getBrowseConfig('admin', $resourceType);

        $query['sort_by'] ??= $defaults['sort_by'] ?? 'id';
        $query['sort_order'] ??= $defaults['sort_order'] ?? 'desc';
        $query['page'] ??= 1;
        $query['per_page'] ??= 25;

        $response = $this->apiService->search($resourceType, $query);

        return new BrowseResult(
            $resourceType,
            $response->getContent(),
            $browse->getColumnsData('admin', $resourceType),
            $response->getTotalResults(),
            $query,
        );
    }

    private function getBrowseService(): OmekaBrowse
    {
        if ($this->browse) {
            return $this->browse;
        }

        $this->browse = $this->legacyApp->getServiceManager()->get('Omeka\Browse');

        return $this->browse;
    }
}
