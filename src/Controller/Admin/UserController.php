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

#[Route('/admin/user')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_user_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        return [
            'result' => $this->browseService->browse('users', $request->query->all()),
        ];
    }

    #[Route('/add', name: 'app_admin_user_add')]
    public function add(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'user', 'add');
    }

    #[Route('/{id}', name: 'app_admin_user_show', requirements: ['id' => '\d+'])]
    #[Template('admin/user/show.html.twig')]
    public function show(int $id): array
    {
        return [
            'user' => $this->api->read('users', $id)->getContent(),
        ];
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'user', 'edit', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'user', 'delete', ['id' => $id]);
    }
}
