<?php

namespace App\Controller\Admin;

use App\Service\LegacyDispatcher;
use App\Service\OmekaApiService;
use App\Service\OmekaBrowseService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/site')]
final class SiteController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_site_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        return [
            'result' => $this->browseService->browse('sites', $request->query->all()),
        ];
    }

    #[Route('/add', name: 'app_admin_site_add')]
    public function add(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'site', 'add');
    }

    #[Route('/{slug}', name: 'app_admin_site_show', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    #[Template('admin/site/show.html.twig')]
    public function show(string $slug): array
    {
        $site = $this->findSiteBySlug($slug);

        return [
            'site' => $site,
        ];
    }

    #[Route('/{slug}/edit', name: 'app_admin_site_edit', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    public function edit(Request $request, string $slug): Response
    {
        $site = $this->findSiteBySlug($slug);
        return $this->legacyDispatcher->dispatch($request, 'site', 'edit', ['site-slug' => $slug, 'id' => $site->id()]);
    }

    #[Route('/{slug}/pages', name: 'app_admin_site_pages', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    #[Template('admin/site/pages.html.twig')]
    public function pages(string $slug): array
    {
        $site = $this->findSiteBySlug($slug);

        return [
            'site' => $site,
            'pages' => $this->api->search('site_pages', ['site_id' => $site->id()])->getContent(),
        ];
    }

    #[Route('/{slug}/navigation', name: 'app_admin_site_navigation', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    public function navigation(Request $request, string $slug): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'site', 'navigation', ['site-slug' => $slug]);
    }

    #[Route('/{slug}/resources', name: 'app_admin_site_resources', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    public function resources(Request $request, string $slug): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'site', 'resources', ['site-slug' => $slug]);
    }

    #[Route('/{slug}/users', name: 'app_admin_site_users', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    public function users(Request $request, string $slug): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'site', 'users', ['site-slug' => $slug]);
    }

    #[Route('/{slug}/theme', name: 'app_admin_site_theme', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    public function theme(Request $request, string $slug): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'site', 'theme', ['site-slug' => $slug]);
    }

    #[Route('/{slug}/delete', name: 'app_admin_site_delete', requirements: ['slug' => '[a-zA-Z0-9_-]+'])]
    public function delete(Request $request, string $slug): Response
    {
        $site = $this->findSiteBySlug($slug);
        return $this->legacyDispatcher->dispatch($request, 'site', 'delete', ['site-slug' => $slug, 'id' => $site->id()]);
    }

    private function findSiteBySlug(string $slug): mixed
    {
        $sites = $this->api->search('sites', ['slug' => $slug])->getContent();
        if (!$sites) {
            throw new NotFoundHttpException('Site not found: ' . $slug);
        }

        return $sites[0];
    }
}
