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
    public function show(Request $request, int $id): Response|array
    {
        $media = $this->api->read('media', $id)->getContent();

        if ($request->query->has('show-details')) {
            return $this->render('admin/media/show-details.html.twig', [
                'media' => $media,
            ]);
        }

        return [
            'media' => $media,
        ];
    }

    #[Route('/{id}/edit', name: 'app_admin_media_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'media', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/delete-confirm', name: 'app_admin_media_delete_confirm', requirements: ['id' => '\d+'])]
    public function deleteConfirm(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'media', 'delete-confirm', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_media_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'media', 'delete', ['id' => $id]);
    }
}
