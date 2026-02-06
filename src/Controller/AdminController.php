<?php

namespace App\Controller;

use App\Service\OmekaApiService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    public function __construct(private readonly OmekaApiService $api)
    {
    }

    #[Route('/admin', name: 'app_admin')]
    #[Template('admin/index.html.twig')]
    public function index(): array
    {
        $sitesResponse = $this->api->search('sites');
        $itemsResponse = $this->api->search('items', ['limit' => 0]);
        $itemSetsResponse = $this->api->search('item_sets', ['limit' => 0]);
        $vocabulariesResponse = $this->api->search('vocabularies', ['limit' => 0]);
        $resourceTemplatesResponse = $this->api->search('resource_templates', ['limit' => 0]);

        return [
            'itemCount' => $itemsResponse->getTotalResults(),
            'itemSetCount' => $itemSetsResponse->getTotalResults(),
            'vocabularyCount' => $vocabulariesResponse->getTotalResults(),
            'resourceTemplateCount' => $resourceTemplatesResponse->getTotalResults(),
            'sites' => $sitesResponse->getContent(),
        ];
    }
}
