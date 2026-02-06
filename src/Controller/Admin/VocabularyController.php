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

#[Route('/admin/vocabulary')]
final class VocabularyController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_vocabulary_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        return [
            'result' => $this->browseService->browse('vocabularies', $request->query->all()),
        ];
    }

    #[Route('/import', name: 'app_admin_vocabulary_import')]
    public function import(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'vocabulary', 'import');
    }

    #[Route('/{id}', name: 'app_admin_vocabulary_show', requirements: ['id' => '\d+'])]
    #[Template('admin/vocabulary/show.html.twig')]
    public function show(int $id): array
    {
        return [
            'vocabulary' => $this->api->read('vocabularies', $id)->getContent(),
        ];
    }

    #[Route('/{id}/edit', name: 'app_admin_vocabulary_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'vocabulary', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/properties', name: 'app_admin_vocabulary_properties', requirements: ['id' => '\d+'])]
    #[Template('admin/vocabulary/properties.html.twig')]
    public function properties(int $id): array
    {
        return [
            'vocabulary' => $this->api->read('vocabularies', $id)->getContent(),
            'properties' => $this->api->search('properties', ['vocabulary_id' => $id])->getContent(),
        ];
    }

    #[Route('/{id}/classes', name: 'app_admin_vocabulary_classes', requirements: ['id' => '\d+'])]
    #[Template('admin/vocabulary/classes.html.twig')]
    public function classes(int $id): array
    {
        return [
            'vocabulary' => $this->api->read('vocabularies', $id)->getContent(),
            'classes' => $this->api->search('resource_classes', ['vocabulary_id' => $id])->getContent(),
        ];
    }

    #[Route('/{id}/delete', name: 'app_admin_vocabulary_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'vocabulary', 'delete', ['id' => $id]);
    }
}
