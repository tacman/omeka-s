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

#[Route('/admin/media')]
final class MediaController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_media_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        return [
            'result' => $this->browseService->browse('media', $request->query->all()),
        ];
    }

    #[Route('/{id}', name: 'app_admin_media_show', requirements: ['id' => '\d+'])]
    #[Template('admin/media/show.html.twig')]
    public function show(int $id): array
    {
        return [
            'media' => $this->api->read('media', $id)->getContent(),
        ];
    }

    #[Route('/{id}/edit', name: 'app_admin_media_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'media', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_media_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'media', 'delete', ['id' => $id]);
    }
}
