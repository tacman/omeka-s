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
 * Symfony-owned /api/ endpoint.
 *
 * All API requests go through Symfony security first, then delegate to the
 * legacy Omeka ApiManager for the actual data operations.  This replaces
 * both the old /api/ (legacy Laminas) and /api-local/ (proxy) routes.
 *
 * Public (unauthenticated) read access is allowed — the Omeka API layer
 * handles visibility filtering via its ACL internally.
 */
#[Route('/api', name: 'app_api_')]
final class ApiProxyController extends AbstractController
{
    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
        private readonly LegacyOmekaAuthService $authService,
    ) {
    }

    /**
     * GET /api — list available API resources.
     */
    #[Route('', name: 'root', methods: ['GET'])]
    public function root(): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();
        $resources = $api->search('api_resources')->getContent();
        usort($resources, fn($a, $b) => strcmp($a->id(), $b->id()));

        $data = array_map(fn($r) => $r->jsonSerialize(), $resources);
        return new JsonResponse($data);
    }

    /**
     * GET /api/{resource} — search/list resources.
     */
    #[Route('/{resource}', name: 'search', requirements: ['resource' => '[a-z_]+'], methods: ['GET'])]
    public function search(Request $request, string $resource): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();

        $query = $request->query->all();

        // Default pagination if not specified
        if (!isset($query['page'])) {
            $query['page'] = 1;
        }

        $response = $api->search($resource, $query);
        $data = array_map(fn($item) => $item->jsonSerialize(), $response->getContent());

        $jsonResponse = new JsonResponse($data);

        // Set total results header for pagination
        $totalResults = $response->getTotalResults();
        if ($totalResults !== null) {
            $jsonResponse->headers->set('Omeka-S-Total-Results', (string) $totalResults);
        }

        return $jsonResponse;
    }

    /**
     * GET /api/{resource}/{id} — read a single resource.
     */
    #[Route('/{resource}/{id}', name: 'show', requirements: ['resource' => '[a-z_]+', 'id' => '\d+'], methods: ['GET'])]
    public function show(Request $request, string $resource, int $id): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();

        try {
            $response = $api->read($resource, $id, $request->query->all());
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 404);
        }

        return new JsonResponse($response->getContent()->jsonSerialize());
    }

    /**
     * POST /api/{resource} — create a new resource.
     */
    #[Route('/{resource}', name: 'create', requirements: ['resource' => '[a-z_]+'], methods: ['POST'])]
    public function create(Request $request, string $resource): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();

        $data = $this->decodeRequestBody($request);
        $fileData = $this->extractFileData($request);

        try {
            $response = $api->create($resource, $data, $fileData);
        } catch (\Omeka\Api\Exception\ValidationException $e) {
            return new JsonResponse(['errors' => $e->getErrorStore()->getErrors()], 422);
        } catch (\Omeka\Api\Exception\PermissionDeniedException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 403);
        }

        return new JsonResponse($response->getContent()->jsonSerialize(), 201);
    }

    /**
     * PUT /api/{resource}/{id} — full update of a resource.
     */
    #[Route('/{resource}/{id}', name: 'update', requirements: ['resource' => '[a-z_]+', 'id' => '\d+'], methods: ['PUT'])]
    public function update(Request $request, string $resource, int $id): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();

        $data = $this->decodeRequestBody($request);

        try {
            $response = $api->update($resource, $id, $data);
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 404);
        } catch (\Omeka\Api\Exception\ValidationException $e) {
            return new JsonResponse(['errors' => $e->getErrorStore()->getErrors()], 422);
        } catch (\Omeka\Api\Exception\PermissionDeniedException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 403);
        }

        return new JsonResponse($response->getContent()->jsonSerialize());
    }

    /**
     * PATCH /api/{resource}/{id} — partial update of a resource.
     */
    #[Route('/{resource}/{id}', name: 'patch', requirements: ['resource' => '[a-z_]+', 'id' => '\d+'], methods: ['PATCH'])]
    public function patch(Request $request, string $resource, int $id): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();

        $data = $this->decodeRequestBody($request);

        try {
            $response = $api->update($resource, $id, $data, [], ['isPartial' => true]);
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 404);
        } catch (\Omeka\Api\Exception\ValidationException $e) {
            return new JsonResponse(['errors' => $e->getErrorStore()->getErrors()], 422);
        } catch (\Omeka\Api\Exception\PermissionDeniedException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 403);
        }

        return new JsonResponse($response->getContent()->jsonSerialize());
    }

    /**
     * DELETE /api/{resource}/{id} — delete a resource.
     */
    #[Route('/{resource}/{id}', name: 'delete', requirements: ['resource' => '[a-z_]+', 'id' => '\d+'], methods: ['DELETE'])]
    public function delete(Request $request, string $resource, int $id): Response
    {
        $this->ensureAuth();
        $api = $this->getApiManager();

        try {
            $response = $api->delete($resource, $id);
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 404);
        } catch (\Omeka\Api\Exception\PermissionDeniedException $e) {
            return new JsonResponse(['errors' => [['message' => $e->getMessage()]]], 403);
        }

        return new JsonResponse(null, 204);
    }

    /**
     * GET /api-context — JSON-LD context (legacy compatibility).
     */
    #[Route('-context', name: 'context', methods: ['GET'])]
    public function context(): Response
    {
        // For now return a minimal context; the full context requires
        // triggering Laminas events which we avoid. Consumers that need
        // the context can get it from the representations' @context key.
        return new JsonResponse(['@context' => new \stdClass()]);
    }

    private function ensureAuth(): void
    {
        // Sync Symfony auth to Omeka auth if a user is logged in.
        // For public/anonymous requests this is a no-op — the Omeka API
        // layer handles visibility filtering via ACL.
        $this->authService->ensureIdentity();
    }

    private function getApiManager(): \Omeka\Api\Manager
    {
        return $this->legacyApp->getServiceManager()->get('Omeka\ApiManager');
    }

    private function decodeRequestBody(Request $request): array
    {
        $contentType = $request->headers->get('Content-Type', '');

        if (str_contains($contentType, 'multipart/form-data')) {
            $content = $request->request->get('data', '{}');
        } else {
            $content = $request->getContent();
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON content must be an object or array.');
        }

        return $data;
    }

    private function extractFileData(Request $request): array
    {
        $files = $request->files->all();
        if (empty($files)) {
            return [];
        }

        // Convert Symfony UploadedFile objects to the array format Omeka expects
        $fileData = [];
        foreach ($files as $key => $file) {
            if ($file) {
                $fileData[$key] = [
                    'tmp_name' => $file->getPathname(),
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'error' => $file->getError(),
                ];
            }
        }

        return $fileData;
    }
}
