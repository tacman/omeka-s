<?php

namespace App\Controller;

use App\Service\OmekaBrowseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminBrowseController extends AbstractController
{
    public function __construct(private readonly OmekaBrowseService $browseService)
    {
    }

    #[Route('/browse/{resourceType}', name: 'app_admin_browse')]
    public function browse(Request $request, string $resourceType): Response
    {
        $query = $request->query->all();
        $result = $this->browseService->browse($resourceType, $query);

        [$legacyRoute, $legacyParams] = $this->legacyRouteForResource($resourceType);

        return $this->render('admin/browse.html.twig', [
            'result' => $result,
            'legacy_route' => $legacyRoute,
            'legacy_params' => $legacyParams,
        ]);
    }

    private function legacyRouteForResource(string $resourceType): array
    {
        if ($resourceType === 'sites') {
            return ['admin/site', []];
        }

        return ['admin/default', [
            'controller' => $this->resourceTypeToController($resourceType),
            'action' => 'browse',
        ]];
    }

    private function resourceTypeToController(string $resourceType): string
    {
        return match ($resourceType) {
            'item_sets' => 'item-set',
            'resource_templates' => 'resource-template',
            default => rtrim($resourceType, 's'),
        };
    }
}
