<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\OmekaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public site controller — handles all /s/{siteSlug} routes.
 *
 * Replaces the legacy Laminas site controllers (IndexController,
 * ItemController, ItemSetController, MediaController, PageController).
 */
#[Route('/s/{siteSlug}', requirements: ['siteSlug' => '[a-zA-Z0-9_-]+'])]
final class PublicSiteController extends AbstractController
{
    public function __construct(private readonly OmekaApiService $api)
    {
    }

    /**
     * Site landing page — redirects to homepage or first page if configured.
     */
    #[Route('', name: 'app_site_show')]
    public function index(string $siteSlug): Response
    {
        $site = $this->findSite($siteSlug);

        // If site has a homepage, render it inline
        $homepage = $site->homepage();
        if ($homepage) {
            return $this->render('site/page.html.twig', [
                'site' => $site,
                'page' => $homepage,
                'siteSlug' => $siteSlug,
                'isHomepage' => true,
            ]);
        }

        // Otherwise list pages
        $pages = $this->api->search('site_pages', [
            'site_id' => $site->id(),
            'sort_by' => 'title',
            'sort_order' => 'asc',
        ])->getContent();

        return $this->render('site/index.html.twig', [
            'site' => $site,
            'pages' => $pages,
        ]);
    }

    /**
     * Site page display.
     */
    #[Route('/page/{pageSlug}', name: 'app_site_page', requirements: ['pageSlug' => '[a-zA-Z0-9_-]+'])]
    public function page(string $siteSlug, string $pageSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $pages = $this->api->search('site_pages', [
            'site_id' => $site->id(),
            'slug' => $pageSlug,
        ])->getContent();

        if (!$pages) {
            throw new NotFoundHttpException(sprintf('Page "%s" not found in site "%s"', $pageSlug, $siteSlug));
        }
        $page = $pages[0];

        return $this->render('site/page.html.twig', [
            'site' => $site,
            'page' => $page,
            'siteSlug' => $siteSlug,
        ]);
    }

    /**
     * Browse items within a site.
     */
    #[Route('/item', name: 'app_site_item_browse')]
    public function itemBrowse(Request $request, string $siteSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $query = $request->query->all();
        $query['site_id'] = $site->id();

        $response = $this->api->search('items', $query);
        $items = $response->getContent();
        $totalResults = $response->getTotalResults();

        return $this->render('site/item/browse.html.twig', [
            'site' => $site,
            'items' => $items,
            'totalResults' => $totalResults,
            'siteSlug' => $siteSlug,
            'query' => $query,
        ]);
    }

    /**
     * Browse items within an item set on a site.
     */
    #[Route('/item-set/{itemSetId}', name: 'app_site_item_set_items', requirements: ['itemSetId' => '\d+'])]
    public function itemSetItems(Request $request, string $siteSlug, int $itemSetId): Response
    {
        $site = $this->findSite($siteSlug);

        $itemSet = $this->api->read('item_sets', $itemSetId)->getContent();

        $query = $request->query->all();
        $query['site_id'] = $site->id();
        $query['item_set_id'] = $itemSetId;

        $response = $this->api->search('items', $query);
        $items = $response->getContent();
        $totalResults = $response->getTotalResults();

        return $this->render('site/item/browse.html.twig', [
            'site' => $site,
            'items' => $items,
            'itemSet' => $itemSet,
            'totalResults' => $totalResults,
            'siteSlug' => $siteSlug,
            'query' => $query,
        ]);
    }

    /**
     * Show a single item on a site.
     */
    #[Route('/item/{id}', name: 'app_site_item_show', requirements: ['id' => '\d+'])]
    public function itemShow(string $siteSlug, int $id): Response
    {
        $site = $this->findSite($siteSlug);

        try {
            $item = $this->api->read('items', $id)->getContent();
        } catch (\Exception $e) {
            throw new NotFoundHttpException(sprintf('Item %d not found', $id));
        }

        return $this->render('site/item/show.html.twig', [
            'site' => $site,
            'item' => $item,
            'resource' => $item,
            'siteSlug' => $siteSlug,
        ]);
    }

    /**
     * Browse item sets on a site.
     */
    #[Route('/item-set', name: 'app_site_item_set_browse')]
    public function itemSetBrowse(Request $request, string $siteSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $query = $request->query->all();
        $query['site_id'] = $site->id();

        $response = $this->api->search('item_sets', $query);
        $itemSets = $response->getContent();
        $totalResults = $response->getTotalResults();

        return $this->render('site/item_set/browse.html.twig', [
            'site' => $site,
            'itemSets' => $itemSets,
            'totalResults' => $totalResults,
            'siteSlug' => $siteSlug,
            'query' => $query,
        ]);
    }

    /**
     * Show a single media resource on a site.
     */
    #[Route('/media/{id}', name: 'app_site_media_show', requirements: ['id' => '\d+'])]
    public function mediaShow(string $siteSlug, int $id): Response
    {
        $site = $this->findSite($siteSlug);

        try {
            $media = $this->api->read('media', $id)->getContent();
        } catch (\Exception $e) {
            throw new NotFoundHttpException(sprintf('Media %d not found', $id));
        }

        return $this->render('site/media/show.html.twig', [
            'site' => $site,
            'media' => $media,
            'resource' => $media,
            'siteSlug' => $siteSlug,
        ]);
    }

    /**
     * Site search.
     */
    #[Route('/search', name: 'app_site_search')]
    public function search(Request $request, string $siteSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $fulltextQuery = $request->query->get('fulltext_search', '');

        $results = [];
        $resourceNames = ['site_pages', 'items'];

        $baseQuery = [
            'fulltext_search' => $fulltextQuery,
            'site_id' => $site->id(),
            'limit' => 10,
        ];

        if ($fulltextQuery) {
            foreach ($resourceNames as $resourceName) {
                $response = $this->api->search($resourceName, $baseQuery);
                $totalResults = $response->getTotalResults();
                if ($totalResults > 0) {
                    $results[$resourceName] = [
                        'resources' => $response->getContent(),
                        'total' => $totalResults,
                    ];
                }
            }
        }

        return $this->render('site/search.html.twig', [
            'site' => $site,
            'query' => $fulltextQuery,
            'results' => $results,
            'siteSlug' => $siteSlug,
        ]);
    }

    /**
     * Page browse — list all pages in a site.
     */
    #[Route('/page', name: 'app_site_page_browse')]
    public function pageBrowse(Request $request, string $siteSlug): Response
    {
        $site = $this->findSite($siteSlug);
        $query = $request->query->all();
        $query['site_id'] = $site->id();

        $response = $this->api->search('site_pages', $query);
        $pages = $response->getContent();

        return $this->render('site/page/browse.html.twig', [
            'site' => $site,
            'pages' => $pages,
            'totalResults' => $response->getTotalResults(),
            'siteSlug' => $siteSlug,
        ]);
    }

    private function findSite(string $siteSlug)
    {
        $sites = $this->api->search('sites', ['slug' => $siteSlug])->getContent();
        if (!$sites) {
            throw new NotFoundHttpException(sprintf('Site "%s" not found', $siteSlug));
        }

        return $sites[0];
    }
}
