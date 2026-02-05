<?php

namespace App\Controller;

use App\Service\OmekaBrowseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony/admin')]
final class AdminLegacyBrowseController extends AbstractController
{
    public function __construct(private readonly OmekaBrowseService $browseService)
    {
    }

    #[Route('/{controller}', name: 'app_admin_legacy_browse', requirements: ['controller' => '[a-zA-Z0-9_-]+'])]
    public function browse(Request $request, string $controller): Response
    {
        $resourceType = $this->controllerToResourceType($controller);
        if (!$resourceType) {
            return $this->redirectToRoute('app_legacy_proxy', [
                'routeName' => 'admin/default',
                'controller' => $controller,
                'action' => 'browse',
            ]);
        }

        $query = $request->query->all();
        $result = $this->browseService->browse($resourceType, $query);

        return $this->render('admin/browse.html.twig', [
            'result' => $result,
            'legacy_route' => $resourceType === 'sites' ? 'admin/site' : 'admin/default',
            'legacy_params' => $resourceType === 'sites'
                ? []
                : ['controller' => $controller, 'action' => 'browse'],
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
