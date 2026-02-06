<?php

namespace App\Controller;

use App\Service\OmekaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
    ) {
    }

    #[Route('/', name: 'app_root')]
    public function index(Request $request): Response
    {
        $query = $request->query->all();
        $query['sort_by'] = $query['sort_by'] ?? 'title';
        $query['sort_order'] = $query['sort_order'] ?? 'asc';

        $response = $this->api->search('sites', $query);
        $sites = $response->getContent();
        $totalResults = $response->getTotalResults();

        // Get installation title from settings if available
        try {
            $sm = $this->api->getApiManager();
            $settings = $sm->getServiceLocator()->get('Omeka\Settings');
            $installationTitle = $settings->get('installation_title', 'Omeka S');
        } catch (\Throwable) {
            $installationTitle = 'Omeka S';
        }

        return $this->render('app/index.html.twig', [
            'installationTitle' => $installationTitle,
            'sites' => $sites,
            'totalResults' => $totalResults,
            'searchQuery' => $request->query->get('fulltext_search', ''),
        ]);
    }

    /**
     * In multi-tenant environments, installation is handled differently.
     */
    #[Route('/install', name: 'app_install')]
    public function install(): Response
    {
        return new Response(
            '<h1>Installation</h1><p>In this environment, installation is managed at the platform level.</p>',
            200,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Database migration status/trigger.
     */
    #[Route('/migrate', name: 'app_migrate')]
    public function migrate(): Response
    {
        return new Response(
            '<h1>Migration</h1><p>Database migrations are managed via Symfony console commands.</p>',
            200,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Maintenance mode page.
     */
    #[Route('/maintenance', name: 'app_maintenance')]
    public function maintenance(): Response
    {
        return new Response(
            '<h1>Maintenance</h1><p>This site is currently undergoing maintenance. Please check back later.</p>',
            503,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Catch-all for routes not yet implemented in Symfony.
     * Shows a clear message instead of redirecting to legacy.
     */
    #[Route('/legacy/{routeName}', name: 'app_legacy_proxy', requirements: ['routeName' => '.+'], priority: -1000)]
    public function legacyProxy(Request $request, string $routeName): Response
    {
        return $this->render('app/not_implemented.html.twig', [
            'route_name' => $routeName,
            'params' => $request->query->all(),
            'request_uri' => $request->getRequestUri(),
        ]);
    }
}
