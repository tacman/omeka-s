<?php

namespace App\Controller;

use App\Service\LegacyDispatcher;
use App\Service\OmekaBrowseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminLegacyBrowseController extends AbstractController
{
    public function __construct(
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
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

    #[Route('/{controller}/{action}', name: 'app_admin_legacy_action', requirements: ['controller' => '[a-z][a-z0-9-]*', 'action' => '[a-z][a-z0-9-]*'], priority: -200)]
    public function legacyAction(Request $request, string $controller, string $action): Response
    {
        return $this->legacyDispatcher->dispatch($request, $controller, $action);
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
