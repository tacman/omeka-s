<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LegacyOmekaApplication;
use App\Service\LegacyOmekaAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Proxies /api-local/ requests to the legacy Omeka API.
 *
 * Legacy JS (resource-form.js, etc.) makes AJAX calls to /api-local/
 * for resource templates, properties, and other data. This controller
 * forwards those requests to the legacy Omeka API manager.
 */
final class ApiLocalProxyController extends AbstractController
{
    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
        private readonly LegacyOmekaAuthService $authService,
    ) {
    }

    #[Route('/api-local/{resource}/{id}', name: 'app_api_local_show', requirements: ['resource' => '[a-z_]+', 'id' => '\d+'], methods: ['GET'])]
    public function show(Request $request, string $resource, int $id): Response
    {
        $this->authService->ensureIdentity();

        $api = $this->legacyApp->getServiceManager()->get('Omeka\ApiManager');
        $response = $api->read($resource, $id, $request->query->all());

        return new JsonResponse($response->getContent()->jsonSerialize());
    }

    #[Route('/api-local/{resource}', name: 'app_api_local_search', requirements: ['resource' => '[a-z_]+'], methods: ['GET'])]
    public function search(Request $request, string $resource): Response
    {
        $this->authService->ensureIdentity();

        $api = $this->legacyApp->getServiceManager()->get('Omeka\ApiManager');
        $response = $api->search($resource, $request->query->all());

        $data = array_map(fn($item) => $item->jsonSerialize(), $response->getContent());

        return new JsonResponse($data);
    }
}
