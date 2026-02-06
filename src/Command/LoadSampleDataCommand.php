<?php

namespace App\Command;

use App\Command\Input\TenantInput;
use App\Tenant\TenantContext;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Mvc\Application as OmekaApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-sample-data',
    description: 'Load sample Omeka sites, item sets, and items',
)]
class LoadSampleDataCommand
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput] TenantInput $tenantInput,
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
        #[Option('Directory of sample media files')]
        string $mediaDir = '/home/tac/Pictures/fortepan',
        #[Option('Assign new items to created sites')]
        bool $assignNewItems = true,
    ): int {
        if ($tenantInput->tenantCode !== null) {
            $this->tenantContext->setTenantCode($tenantInput->tenantCode);
        }

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
        $hasCreator = $this->hasProperty($api, 'dcterms:creator');
        $hasSubject = $this->hasProperty($api, 'dcterms:subject');
        $resourceClassId = $this->getFirstId($api, 'resource_classes');
        $resourceTemplateId = $this->getFirstId($api, 'resource_templates');

        $sites = max(0, $sites);
        $itemSets = max(0, $itemSets);
        $items = max(0, $items);

        $createdSites = [];
        $createdItemSets = [];
        $createdItems = [];
        $createdMedia = [];

        $mediaFiles = $this->loadMediaFiles($io, $mediaDir);
        $publicMediaUrlBase = $this->ensurePublicMediaLink($io, $mediaDir);

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
            $site = $this->createResource($io, $api, 'sites', $siteData);
            $createdSites[] = $site;
        }

        for ($i = 1; $i <= $itemSets; $i++) {
            $title = $i === 1 ? 'Sample Item Set' : "Sample Item Set $i";
            $data = [
                'dcterms:title' => [$this->literalValue($title)],
                'o:is_public' => true,
            ];
            if ($resourceClassId) {
                $data['o:resource_class'] = ['o:id' => $resourceClassId];
            }
            if ($resourceTemplateId) {
                $data['o:resource_template'] = ['o:id' => $resourceTemplateId];
            }
            if ($hasDescription) {
                $data['dcterms:description'] = [
                    $this->literalValue("Generated sample item set $i."),
                ];
            }
            if ($hasCreator) {
                $data['dcterms:creator'] = [$this->literalValue('Sample Creator')];
            }
            if ($hasSubject) {
                $data['dcterms:subject'] = [$this->literalValue('Sample Subject')];
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
            if ($resourceClassId) {
                $data['o:resource_class'] = ['o:id' => $resourceClassId];
            }
            if ($resourceTemplateId) {
                $data['o:resource_template'] = ['o:id' => $resourceTemplateId];
            }
            if ($hasDescription) {
                $data['dcterms:description'] = [
                    $this->literalValue("Generated sample item $i."),
                ];
            }
            if ($hasCreator) {
                $data['dcterms:creator'] = [$this->literalValue(sprintf('Creator %d', (($i - 1) % 3) + 1))];
            }
            if ($hasSubject) {
                $data['dcterms:subject'] = [$this->literalValue(sprintf('Subject %d', (($i - 1) % 4) + 1))];
            }
            if ($itemSetIds) {
                $itemSetId = $itemSetIds[($i - 1) % count($itemSetIds)];
                $data['o:item_set'] = [$itemSetId];
            }
            if (!$assignNewItems && $siteIds) {
                $data['o:site'] = [['o:id' => $siteIds[0]]];
            }
            $createdItems[] = $this->createResource($io, $api, 'items', $data);
        }

        // Create sample media (attach to items)
        foreach ($createdItems as $index => $item) {
            if ($index % 2 !== 0 || empty($mediaFiles) || $publicMediaUrlBase === null) {
                continue;
            }

            $filePath = $mediaFiles[$index % count($mediaFiles)];
            $filename = basename($filePath);
            $imageUrl = rtrim($publicMediaUrlBase, '/') . '/' . $filename;
            $mediaData = [
                'o:item' => ['o:id' => $item->id()],
                'o:ingester' => 'html',
                'html' => sprintf('<figure><img src="%s" alt="%s"></figure>', $imageUrl, htmlspecialchars($filename, ENT_QUOTES)),
                'o:label' => sprintf('Sample Image %d', $index + 1),
                'o:source' => $imageUrl,
                'o:is_public' => true,
            ];
            $createdMedia[] = $this->createResource($io, $api, 'media', $mediaData);
        }

        // Create pages and navigation for each site now that items/media exist
        foreach ($createdSites as $i => $site) {
            $pages = $this->createSamplePages($io, $api, $site, $i + 1, $createdItems, $createdMedia);
            if ($pages) {
                $navigation = $this->createSampleNavigation($site, $pages);
                $api->update('sites', $site->id(), [
                    'o:navigation' => $navigation,
                    'o:homepage' => ['o:id' => $pages[0]->id()],
                ], [], ['isPartial' => true]);
            }
        }

        $io->success(sprintf(
            'Created %d site(s), %d item set(s), %d item(s), and %d media.',
            count($createdSites),
            count($createdItemSets),
            count($createdItems),
            count($createdMedia)
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

    private function createSamplePages(
        SymfonyStyle $io,
        ApiManager $api,
        $site,
        int $index,
        array $items,
        array $media
    ): array
    {
        $pages = [];
        $pageSpecs = [
            [
                'title' => $index === 1 ? 'Welcome' : "Welcome $index",
                'slug' => $index === 1 ? 'welcome' : "welcome-$index",
                'html' => sprintf('<p>This is the welcome page for %s.</p>', $site->title()),
            ],
            [
                'title' => $index === 1 ? 'About' : "About $index",
                'slug' => $index === 1 ? 'about' : "about-$index",
                'html' => '<p>Sample content page created by the load-sample-data command.</p>',
            ],
            [
                'title' => $index === 1 ? 'Collections' : "Collections $index",
                'slug' => $index === 1 ? 'collections' : "collections-$index",
                'html' => '<p>Explore items and item sets curated for this site.</p>',
            ],
            [
                'title' => $index === 1 ? 'Contact' : "Contact $index",
                'slug' => $index === 1 ? 'contact' : "contact-$index",
                'html' => '<p>Contact us at <a href="mailto:info@example.org">info@example.org</a>.</p>',
            ],
        ];

        foreach ($pageSpecs as $spec) {
            $slug = $this->makeUniquePageSlug($api, $site->id(), $this->slugify($spec['slug']));
            $blocks = [
                [
                    'o:layout' => 'pageTitle',
                    'o:data' => [],
                ],
                [
                    'o:layout' => 'html',
                    'o:data' => ['html' => $spec['html']],
                ],
            ];

            $mediaAttachment = $media[($index - 1) % max(1, count($media))] ?? null;
            $itemAttachment = null;
            if ($mediaAttachment && method_exists($mediaAttachment, 'item')) {
                $itemAttachment = $mediaAttachment->item();
            }
            if (!$itemAttachment) {
                $itemAttachment = $items[($index - 1) % max(1, count($items))] ?? null;
            }

            if ($mediaAttachment && $itemAttachment) {
                $blocks[] = [
                    'o:layout' => 'media',
                    'o:data' => ['caption' => 'Sample media'],
                    'o:attachment' => [
                        [
                            'o:item' => ['o:id' => $itemAttachment->id()],
                            'o:media' => ['o:id' => $mediaAttachment->id()],
                            'o:caption' => 'Sample media attachment',
                        ],
                    ],
                ];
            }

            $pageData = [
                'o:site' => ['o:id' => $site->id()],
                'o:title' => $spec['title'],
                'o:slug' => $slug,
                'o:is_public' => true,
                'o:block' => $blocks,
            ];
            $pages[] = $this->createResource($io, $api, 'site_pages', $pageData);
        }

        return $pages;
    }

    private function createSampleNavigation($site, array $pages): array
    {
        $navigation = [];

        // Add first two pages
        foreach (array_slice($pages, 0, 2) as $page) {
            $navigation[] = [
                'type' => 'page',
                'links' => [],
                'data' => ['id' => $page->id(), 'label' => null],
            ];
        }

        // Add a browse items link
        $navigation[] = [
            'type' => 'link',
            'links' => [],
            'data' => [
                'label' => 'Browse items',
                'url' => sprintf('/s/%s/item', $site->slug()),
            ],
        ];

        // Add an external link
        $navigation[] = [
            'type' => 'link',
            'links' => [],
            'data' => [
                'label' => 'About this project',
                'url' => 'https://example.org',
            ],
        ];

        return $navigation;
    }

    private function makeUniquePageSlug(ApiManager $api, int $siteId, string $slug): string
    {
        $base = $slug !== '' ? $slug : 'page';
        $candidate = $base;
        $suffix = 1;

        while (true) {
            $existing = $api->search('site_pages', [
                'site_id' => $siteId,
                'slug' => $candidate,
            ])->getContent();
            if (empty($existing)) {
                return $candidate;
            }
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }
    }

    private function getFirstId(ApiManager $api, string $resource): ?int
    {
        $response = $api->search($resource, ['limit' => 1]);
        $results = $response->getContent();
        if (!$results) {
            return null;
        }

        $first = $results[0];
        return $first->id();
    }

    private function createResource(SymfonyStyle $io, ApiManager $api, string $resource, array $data, array $fileData = [])
    {
        try {
            return $api->create($resource, $data, $fileData)->getContent();
        } catch (ValidationException $e) {
            $errors = $e->getErrorStore()->getErrors();
            $io->error(sprintf('Validation failed creating %s.', $resource));
            $io->writeln(json_encode($errors, JSON_PRETTY_PRINT));
            throw $e;
        }
    }

    private function ensurePublicMediaLink(SymfonyStyle $io, string $mediaDir): ?string
    {
        $projectRoot = dirname(__DIR__, 2);
        $publicDir = $projectRoot . '/public';
        $linkPath = $publicDir . '/fortepan';

        if (!is_dir($mediaDir)) {
            $io->warning(sprintf('Media directory not found: %s', $mediaDir));
            return null;
        }

        if (!is_dir($publicDir)) {
            $io->warning(sprintf('Public directory not found: %s', $publicDir));
            return null;
        }

        if (is_link($linkPath) || is_dir($linkPath)) {
            return '/fortepan';
        }

        if (@symlink($mediaDir, $linkPath) === false) {
            $io->warning(sprintf('Failed to create symlink %s -> %s', $linkPath, $mediaDir));
            return null;
        }

        return '/fortepan';
    }

    private function loadMediaFiles(SymfonyStyle $io, string $mediaDir): array
    {
        $path = $mediaDir;
        if (!is_dir($path)) {
            $io->warning(sprintf('Media directory not found: %s', $path));
            return [];
        }

        $files = array_merge(
            glob($path . '/*.jpg') ?: [],
            glob($path . '/*.jpeg') ?: [],
            glob($path . '/*.JPG') ?: [],
            glob($path . '/*.JPEG') ?: []
        );

        $files = array_values(array_filter($files, static fn($file) => is_file($file) && is_readable($file)));

        if (!$files) {
            $io->warning(sprintf('No media files found in %s', $path));
            return [];
        }

        return $files;
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
