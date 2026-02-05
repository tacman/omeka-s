<?php

namespace App\Controller;

use App\Service\OmekaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony')]
final class AdminController extends AbstractController
{
    public function __construct(private readonly OmekaApiService $api)
    {
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $sitesResponse = $this->api->search('sites');
        $itemsResponse = $this->api->search('items', ['limit' => 0]);
        $itemSetsResponse = $this->api->search('item_sets', ['limit' => 0]);
        $vocabulariesResponse = $this->api->search('vocabularies', ['limit' => 0]);
        $resourceTemplatesResponse = $this->api->search('resource_templates', ['limit' => 0]);

        return $this->render('admin/index.html.twig', [
            'itemCount' => $itemsResponse->getTotalResults(),
            'itemSetCount' => $itemSetsResponse->getTotalResults(),
            'vocabularyCount' => $vocabulariesResponse->getTotalResults(),
            'resourceTemplateCount' => $resourceTemplatesResponse->getTotalResults(),
            'sites' => $sitesResponse->getContent(),
            'legacy_route' => 'admin',
        ]);
    }
}
