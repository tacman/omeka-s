<?php

namespace App\Controller\Admin;

use App\Service\LegacyDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/asset')]
final class AssetController extends AbstractController
{
    public function __construct(
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_asset_browse')]
    public function browse(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'asset', 'browse');
    }

    #[Route('/add', name: 'app_admin_asset_add')]
    public function add(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'asset', 'add');
    }

    #[Route('/{id}', name: 'app_admin_asset_show', requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'asset', 'show', ['id' => $id]);
    }

    #[Route('/{id}/edit', name: 'app_admin_asset_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'asset', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_asset_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'asset', 'delete', ['id' => $id]);
    }
}
