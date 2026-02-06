<?php

declare(strict_types=1);

namespace App\Service;

use Omeka\Api\Manager;
use Omeka\Api\Response;

final class OmekaApiService
{
    private ?Manager $apiManager = null;

    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
        private readonly LegacyOmekaAuthService $authService,
    )
    {
    }

    public function getApiManager(): Manager
    {
        if ($this->apiManager) {
            return $this->apiManager;
        }

        $this->authService->ensureIdentity();
        $this->apiManager = $this->legacyApp->getServiceManager()->get('Omeka\ApiManager');

        return $this->apiManager;
    }

    public function search(string $resourceType, array $query = []): Response
    {
        return $this->getApiManager()->search($resourceType, $query);
    }

    public function read(string $resourceType, int|string $id): Response
    {
        return $this->getApiManager()->read($resourceType, $id);
    }
}
