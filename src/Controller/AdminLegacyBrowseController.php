<?php

namespace App\Controller;

use App\Service\OmekaBrowseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminLegacyBrowseController extends AbstractController
{
    public function __construct(private readonly OmekaBrowseService $browseService)
    {
    }

    #[Route('/{controller}', name: 'app_admin_legacy_browse', requirements: ['controller' => '[a-zA-Z0-9_-]+'], priority: -100)]
    public function browse(Request $request, string $controller): Response
    {
        $resourceType = $this->controllerToResourceType($controller);
        if (!$resourceType) {
            // Show "not implemented" instead of redirecting to legacy
            return $this->render('app/not_implemented.html.twig', [
                'route_name' => "admin/{$controller}/browse",
                'params' => $request->query->all(),
                'request_uri' => $request->getRequestUri(),
            ]);
        }

        $query = $request->query->all();
        $result = $this->browseService->browse($resourceType, $query);

        return $this->render('admin/browse.html.twig', [
            'result' => $result,
        ]);
    }

    private function controllerToResourceType(string $controller): ?string
    {
        return match ($controller) {
            'item' => 'items',
            'item-set' => 'item_sets',
            'media' => 'media',
            'site' => 'sites',
            'vocabulary' => 'vocabularies',
            'resource-template' => 'resource_templates',
            default => null,
        };
    }
}
