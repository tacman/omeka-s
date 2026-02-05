<?php

namespace App\Command;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Mvc\Application as OmekaApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-sample-data',
    description: 'Load sample Omeka sites, item sets, and items',
)]
class LoadSampleDataCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[Option('Admin email to run as')]
        string $adminEmail = 'admin@example.com',
        #[Option('Number of sites to create')]
        int $sites = 1,
        #[Option('Number of item sets to create')]
        int $itemSets = 2,
        #[Option('Number of items to create')]
        int $items = 12,
        #[Option('Theme to use for sites')]
        string $theme = 'default',
        #[Option('Assign new items to created sites')]
        bool $assignNewItems = true,
    ): int {
        $projectRoot = dirname(__DIR__, 2);
        $bootstrapPath = $projectRoot . '/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        } elseif (!defined('OMEKA_PATH')) {
            define('OMEKA_PATH', $projectRoot);
        }

        $config = require OMEKA_PATH . '/application/config/application.config.php';
        $application = OmekaApplication::init($config);
        $services = $application->getServiceManager();

        $services->get('Omeka\ModuleManager');

        $entityManager = $services->get('Omeka\EntityManager');
        $adminUser = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $adminEmail]);
        if (!$adminUser) {
            $io->error(sprintf('Admin user not found for email: %s', $adminEmail));
            return Command::FAILURE;
        }

        $auth = $services->get('Omeka\AuthenticationService');
        $auth->getStorage()->write($adminUser);

        $api = $services->get('Omeka\ApiManager');
        if (!$this->hasProperty($api, 'dcterms:title')) {
            $io->error('Missing dcterms:title property. Run app:install-defaults first.');
            return Command::FAILURE;
        }
        $hasDescription = $this->hasProperty($api, 'dcterms:description');

        $sites = max(0, $sites);
        $itemSets = max(0, $itemSets);
        $items = max(0, $items);

        $createdSites = [];
        $createdItemSets = [];

        for ($i = 1; $i <= $sites; $i++) {
            $title = $i === 1 ? 'Sample Site' : "Sample Site $i";
            $slug = $i === 1 ? 'sample-site' : "sample-site-$i";
            $siteData = [
                'o:title' => $title,
                'o:slug' => $this->makeUniqueSiteSlug($api, $this->slugify($slug)),
                'o:theme' => $theme,
                'o:is_public' => true,
                'o:assign_new_items' => $assignNewItems,
            ];
            $createdSites[] = $this->createResource($io, $api, 'sites', $siteData);
        }

        for ($i = 1; $i <= $itemSets; $i++) {
            $title = $i === 1 ? 'Sample Item Set' : "Sample Item Set $i";
            $data = [
                'dcterms:title' => [$this->literalValue($title)],
                'o:is_public' => true,
            ];
            if ($hasDescription) {
                $data['dcterms:description'] = [
                    $this->literalValue("Generated sample item set $i."),
                ];
            }
            $createdItemSets[] = $this->createResource($io, $api, 'item_sets', $data);
        }

        $siteIds = array_map(fn ($site) => $site->id(), $createdSites);
        $itemSetIds = array_map(fn ($itemSet) => $itemSet->id(), $createdItemSets);

        for ($i = 1; $i <= $items; $i++) {
            $title = "Sample Item $i";
            $data = [
                'dcterms:title' => [$this->literalValue($title)],
                'o:is_public' => true,
            ];
            if ($hasDescription) {
                $data['dcterms:description'] = [
                    $this->literalValue("Generated sample item $i."),
                ];
            }
            if ($itemSetIds) {
                $itemSetId = $itemSetIds[($i - 1) % count($itemSetIds)];
                $data['o:item_set'] = [$itemSetId];
            }
            if (!$assignNewItems && $siteIds) {
                $data['o:site'] = [['o:id' => $siteIds[0]]];
            }
            $this->createResource($io, $api, 'items', $data);
        }

        $io->success(sprintf(
            'Created %d site(s), %d item set(s), and %d item(s).',
            count($createdSites),
            count($createdItemSets),
            $items
        ));

        return Command::SUCCESS;
    }

    private function hasProperty(ApiManager $api, string $term): bool
    {
        $result = $api->search('properties', ['term' => $term])->getContent();
        return !empty($result);
    }

    private function makeUniqueSiteSlug(ApiManager $api, string $slug): string
    {
        $base = $slug !== '' ? $slug : 'site';
        $candidate = $base;
        $suffix = 1;

        while (true) {
            $existing = $api->search('sites', ['slug' => $candidate])->getContent();
            if (empty($existing)) {
                return $candidate;
            }
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }
    }

    private function createResource(SymfonyStyle $io, ApiManager $api, string $resource, array $data)
    {
        try {
            return $api->create($resource, $data)->getContent();
        } catch (ValidationException $e) {
            $errors = $e->getErrorStore()->getErrors();
            $io->error(sprintf('Validation failed creating %s.', $resource));
            $io->writeln(json_encode($errors, JSON_PRETTY_PRINT));
            throw $e;
        }
    }

    private function literalValue(string $value): array
    {
        return [
            'type' => 'literal',
            'property_id' => 'auto',
            '@value' => $value,
        ];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'site';
    }
}
