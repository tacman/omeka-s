<?php

namespace App\Controller;

use App\Service\OmekaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony')]
final class PublicSiteController extends AbstractController
{
    public function __construct(private readonly OmekaApiService $api)
    {
    }

    #[Route('/s/{siteSlug}', name: 'app_site_public', requirements: ['siteSlug' => '[a-zA-Z0-9_-]+'])]
    public function index(string $siteSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $pages = $this->api->search('site_pages', ['site_id' => $site->id(), 'sort_by' => 'title', 'sort_order' => 'asc'])->getContent();

        return $this->render('site/index.html.twig', [
            'site' => $site,
            'pages' => $pages,
        ]);
    }

    #[Route('/s/{siteSlug}/page/{pageSlug}', name: 'app_site_page', requirements: ['siteSlug' => '[a-zA-Z0-9_-]+', 'pageSlug' => '[a-zA-Z0-9_-]+'])]
    public function page(string $siteSlug, string $pageSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $pages = $this->api->search('site_pages', [
            'site_id' => $site->id(),
            'slug' => $pageSlug,
        ])->getContent();
        if (!$pages) {
            throw new NotFoundHttpException(sprintf('Page not found: %s', $pageSlug));
        }
        $page = $pages[0];

        return $this->render('site/page.html.twig', [
            'site' => $site,
            'page' => $page,
        ]);
    }

    private function findSite(string $siteSlug)
    {
        $sites = $this->api->search('sites', ['slug' => $siteSlug])->getContent();
        if (!$sites) {
            throw new NotFoundHttpException(sprintf('Site not found: %s', $siteSlug));
        }

        return $sites[0];
    }
}
