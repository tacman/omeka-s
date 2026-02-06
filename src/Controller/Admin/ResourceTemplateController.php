<?php

namespace App\Controller\Admin;

use App\Service\LegacyDispatcher;
use App\Service\OmekaApiService;
use App\Service\OmekaBrowseService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/resource-template')]
final class ResourceTemplateController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_resource_template_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        return [
            'result' => $this->browseService->browse('resource_templates', $request->query->all()),
        ];
    }

    #[Route('/add', name: 'app_admin_resource_template_add')]
    public function add(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'resource-template', 'add');
    }

    #[Route('/import', name: 'app_admin_resource_template_import')]
    public function import(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'resource-template', 'import');
    }

    #[Route('/{id}', name: 'app_admin_resource_template_show', requirements: ['id' => '\d+'])]
    #[Template('admin/resource_template/show.html.twig')]
    public function show(int $id): array
    {
        return [
            'template' => $this->api->read('resource_templates', $id)->getContent(),
        ];
    }

    #[Route('/{id}/edit', name: 'app_admin_resource_template_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'resource-template', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_resource_template_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'resource-template', 'delete', ['id' => $id]);
    }
}
