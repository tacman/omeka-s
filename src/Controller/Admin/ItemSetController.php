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

#[Route('/admin/item-set')]
final class ItemSetController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_item_set_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        return [
            'result' => $this->browseService->browse('item_sets', $request->query->all()),
        ];
    }

    #[Route('/add', name: 'app_admin_item_set_add')]
    public function add(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'item-set', 'add');
    }

    #[Route('/{id}', name: 'app_admin_item_set_show', requirements: ['id' => '\d+'])]
    #[Template('admin/item_set/show.html.twig')]
    public function show(Request $request, int $id): Response|array
    {
        $itemSet = $this->api->read('item_sets', $id)->getContent();

        if ($request->query->has('show-details')) {
            return $this->render('admin/item_set/show-details.html.twig', [
                'itemSet' => $itemSet,
            ]);
        }

        return [
            'itemSet' => $itemSet,
        ];
    }

    #[Route('/{id}/edit', name: 'app_admin_item_set_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'item-set', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/delete-confirm', name: 'app_admin_item_set_delete_confirm', requirements: ['id' => '\d+'])]
    public function deleteConfirm(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'item-set', 'delete-confirm', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_item_set_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'item-set', 'delete', ['id' => $id]);
    }
}
