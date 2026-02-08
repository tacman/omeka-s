# Survos Omeka: Symfony Bundle Ecosystem for Museum Collections

## Vision

See https://github.com/omeka-s-modules

Convert Omeka-S modules into proper Symfony bundles, creating a modern, Composer-native ecosystem for museum and cultural heritage applications. Replace Laminas dependencies with Symfony components while maintaining Omeka-S functionality and compatibility.

## Architecture Overview

### Bundle Ecosystem Structure
````
survos-omeka/
├── core-bundle/              # Foundation - shared infrastructure
├── value-suggest-bundle/     # External vocabularies & autocomplete
├── mapping-bundle/           # Geographic data & maps
├── custom-vocab-bundle/      # Custom controlled vocabularies
├── iiif-bundle/             # IIIF image server integration
├── numeric-data-types-bundle/
├── meili-search-bundle/     # Hybrid semantic search (showcase)
└── [other converted modules]
````

### Installation Pattern
````bash
# Instead of traditional Omeka-S module installation
composer require survos-omeka/core-bundle
composer require survos-omeka/value-suggest-bundle
composer require survos-omeka/mapping-bundle
composer require survos-omeka/meili-search-bundle

# Bundles auto-register in config/bundles.php
# Per-tenant activation managed in database
````

## Core Bundle (`survos-omeka/core-bundle`)

### Provides Foundation Infrastructure
````json
{
   "name": "survos-omeka/core-bundle",
   "type": "symfony-bundle",
   "description": "Core infrastructure for Symfony-based Omeka ecosystem",
   "require": {
       "php": "^8.3",
       "symfony/framework-bundle": "^7.0",
       "doctrine/doctrine-bundle": "^2.0",
       "doctrine/orm": "^3.0"
   }
}
```

### Core Bundle Features

1. **Multi-tenant Connection Manager**
  - Dynamic Doctrine connection switching
  - Tenant detection (subdomain, header, parameter)
  - Per-tenant database isolation

2. **Module Activation System**
  - Database-driven bundle enablement per tenant
  - Configuration storage in database (not .ini files)
  - Admin UI for bundle management

3. **View System**
  - Laminas view helpers → Twig extensions
  - PHTML compatibility layer (transitional)
  - Omeka-specific Twig functions

4. **Security & ACL**
  - Omeka ACL → Symfony Security voters
  - Role mapping (admin, editor, viewer)
  - Resource-based permissions

5. **Base Entities & Repositories**
  - Item, ItemSet, Media, Property, ResourceClass
  - Site, User, Vocabulary
  - Shared traits and interfaces

6. **API Compatibility Layer**
  - Omeka-S API → Symfony controllers
  - JSON-LD support
  - REST endpoints

7. **Event System**
  - Laminas EventManager patterns → Symfony EventDispatcher
  - Standard event names and objects
  - Hook points for bundles

### Core Bundle Structure
```
core-bundle/
├── composer.json
├── src/
│   ├── SurvosOmekaCoreBundle.php
│   ├── Entity/
│   │   ├── Item.php
│   │   ├── ItemSet.php
│   │   ├── Media.php
│   │   ├── Site.php
│   │   └── User.php
│   ├── Repository/
│   ├── Controller/
│   │   ├── Admin/
│   │   └── Api/
│   ├── Security/
│   │   ├── Voter/
│   │   └── AccessManager.php
│   ├── MultiTenant/
│   │   ├── TenantResolver.php
│   │   ├── ConnectionManager.php
│   │   └── TenantContext.php
│   ├── Module/
│   │   ├── ModuleManager.php
│   │   ├── ConfigStorage.php
│   │   └── ActivationManager.php
│   ├── DependencyInjection/
│   ├── EventSubscriber/
│   ├── Twig/
│   │   └── Extension/
│   │       ├── UrlExtension.php
│   │       ├── NavigationExtension.php
│   │       └── MediaExtension.php
│   └── Command/
│       ├── TenantCreateCommand.php
│       └── ModuleListCommand.php
├── config/
│   ├── services.yaml
│   ├── routes.yaml
│   └── doctrine.yaml
├── templates/
│   └── admin/
└── tests/
````

## Module → Bundle Conversion Tool

### Automated Conversion Command
````bash
bin/console omeka:bundle-create \
 --from-module=omeka-s/module-ValueSuggest \
 --output-dir=../survos-omeka-bundles/value-suggest-bundle \
 --namespace="Survos\OmekaBundle\ValueSuggest" \
 --author="Tac Tacelosky <tac@survos.com>"
````

### Conversion Pipeline

Uses `nikic/php-parser` and `nette/php-generator` for AST transformation:

1. **Parse Omeka-S Module**
- Module.php (module class)
- config/module.config.php (routes, services, events)
- config/module.ini (metadata)
- src/ (controllers, services, forms)
- view/ (templates)

2. **Extract Patterns**
- Event listeners → EventSubscribers
- Service factories → Service definitions
- Routes → Controller attributes
- View helpers → Twig extensions
- Forms → Symfony Forms
- Jobs → Messenger handlers

3. **Generate Symfony Code**
- Proper bundle structure
- PSR-4 autoloading
- composer.json with dependencies
- PHPUnit test stubs
- README.md

### Conversion Patterns

#### Routes: Laminas → Symfony Attributes
````php
// Laminas config/module.config.php
'router' => [
   'routes' => [
       'admin/value-suggest' => [
           'type' => 'Literal',
           'options' => [
               'route' => '/admin/value-suggest',
               'defaults' => [
                   'controller' => Controller\IndexController::class,
                   'action' => 'browse',
               ],
           ],
       ],
   ],
],

// Generated Symfony Controller
namespace Survos\OmekaBundle\ValueSuggest\Controller;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/value-suggest')]
class IndexController extends AbstractController
{
   #[Route('', name: 'admin_value_suggest_browse', methods: ['GET'])]
   public function browse(): Response
   {
       return $this->render('@ValueSuggest/index/browse.html.twig', [
           'suggesters' => $this->suggesterManager->getRegistered(),
       ]);
   }
}
````

#### Events: Laminas EventManager → Symfony EventDispatcher
````php
// Laminas Module.php
public function attachListeners(SharedEventManagerInterface $sharedEvents)
{
   $sharedEvents->attach(
       'Omeka\Controller\Admin\Item',
       'view.show.after',
       [$this, 'handleViewShowAfter']
   );
}

// Generated Symfony EventSubscriber
namespace Survos\OmekaBundle\ValueSuggest\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ItemViewSubscriber implements EventSubscriberInterface
{
   public static function getSubscribedEvents(): array
   {
       return [
           'omeka.admin.item.view.show.after' => 'handleViewShowAfter',
       ];
   }
   
   public function handleViewShowAfter(ViewEvent $event): void
   {
       // Converted logic
   }
}
````

#### Services: Laminas ServiceManager → Symfony DI
````php
// Laminas config/module.config.php
'service_manager' => [
   'factories' => [
       'ValueSuggest\DataType\Manager' => Service\DataTypeManagerFactory::class,
   ],
   'invokables' => [
       'ValueSuggest\Suggester\Geonames' => Suggester\Geonames::class,
   ],
],

// Generated Symfony services.yaml
services:
   Survos\OmekaBundle\ValueSuggest\:
       resource: '../src/'
       exclude:
           - '../src/DependencyInjection/'
           - '../src/Entity/'

   Survos\OmekaBundle\ValueSuggest\DataType\Manager:
       factory: ['@Survos\OmekaBundle\ValueSuggest\Service\DataTypeManagerFactory', 'create']
       
   Survos\OmekaBundle\ValueSuggest\Suggester\Geonames:
       tags: ['omeka.suggester']
````

#### Jobs: Omeka Jobs → Symfony Messenger
````php
// Omeka-S Job
namespace ValueSuggest\Job;

class UpdateSuggestions extends AbstractJob
{
   public function perform()
   {
       $itemId = $this->getArg('item_id');
       // Job logic
   }
}

// Generated Symfony Message + Handler
namespace Survos\OmekaBundle\ValueSuggest\Message;

class UpdateSuggestionsMessage
{
   public function __construct(
       public readonly int $itemId,
   ) {}
}

#[AsMessageHandler]
class UpdateSuggestionsHandler
{
   public function __invoke(UpdateSuggestionsMessage $message): void
   {
       // Converted job logic
   }
}
````

#### Views: PHTML → Twig (Incremental)
````php
// Phase 1: Keep PHTML with compatibility layer
// Register view directory with Symfony PHP templating

// Phase 2: Convert to Twig
bin/console omeka:convert-views ValueSuggest --to-twig

// Omeka PHTML
<?php echo $this->escapeHtml($item->displayTitle()); ?>
<?php echo $this->hyperlink($item->title(), $item->url()); ?>

// Generated Twig
{{ item.displayTitle|escape }}
{{ omeka_link(item.title, item.url) }}
````

## Example Bundle: ValueSuggest

### composer.json
````json
{
   "name": "survos-omeka/value-suggest-bundle",
   "type": "symfony-bundle",
   "description": "Symfony bundle port of Omeka-S ValueSuggest module - external vocabulary autocomplete",
   "keywords": ["omeka", "symfony", "museum", "glam", "vocabulary", "autocomplete"],
   "license": "GPL-3.0-or-later",
   "authors": [
       {
           "name": "Tac Tacelosky",
           "email": "tac@survos.com"
       }
   ],
   "require": {
       "php": "^8.3",
       "symfony/framework-bundle": "^7.0",
       "survos-omeka/core-bundle": "^1.0",
       "symfony/http-client": "^7.0"
   },
   "require-dev": {
       "phpunit/phpunit": "^11.0",
       "symfony/phpunit-bridge": "^7.0"
   },
   "autoload": {
       "psr-4": {
           "Survos\\OmekaBundle\\ValueSuggest\\": "src/"
       }
   },
   "autoload-dev": {
       "psr-4": {
           "Survos\\OmekaBundle\\ValueSuggest\\Tests\\": "tests/"
       }
   }
}
```

### Bundle Structure
```
value-suggest-bundle/
├── composer.json
├── README.md
├── LICENSE
├── src/
│   ├── ValueSuggestBundle.php
│   ├── Controller/
│   │   └── Admin/
│   │       └── IndexController.php
│   ├── DataType/
│   │   ├── AbstractDataType.php
│   │   └── ValuesuggestDataType.php
│   ├── Suggester/
│   │   ├── SuggesterInterface.php
│   │   ├── Geonames.php
│   │   ├── Getty.php
│   │   ├── Loc.php
│   │   ├── Oclc.php
│   │   └── Wikidata.php
│   ├── Service/
│   │   └── SuggesterManager.php
│   ├── EventSubscriber/
│   │   ├── FormSubscriber.php
│   │   └── ApiSubscriber.php
│   ├── DependencyInjection/
│   │   ├── Configuration.php
│   │   └── ValueSuggestExtension.php
│   └── Twig/
│       └── ValueSuggestExtension.php
├── config/
│   ├── services.yaml
│   └── routes.yaml
├── templates/
│   └── admin/
│       └── index/
│           └── browse.html.twig
└── tests/
   └── Suggester/
       └── GeonamesTest.php
````

## Multi-Tenant Configuration

### Database Schema
````sql
-- Per-tenant module activation
CREATE TABLE module_activation (
   tenant_id INT NOT NULL,
   bundle_name VARCHAR(255) NOT NULL,
   enabled BOOLEAN DEFAULT FALSE,
   installed_at DATETIME,
   PRIMARY KEY (tenant_id, bundle_name),
   INDEX idx_tenant (tenant_id),
   INDEX idx_enabled (enabled)
);

-- Per-tenant module configuration
CREATE TABLE module_config (
   id INT AUTO_INCREMENT PRIMARY KEY,
   tenant_id INT NOT NULL,
   bundle_name VARCHAR(255) NOT NULL,
   config_key VARCHAR(255) NOT NULL,
   config_value JSON,
   updated_at DATETIME,
   UNIQUE KEY uk_tenant_bundle_key (tenant_id, bundle_name, config_key),
   INDEX idx_tenant_bundle (tenant_id, bundle_name)
);
````

### Tenant-Aware Service
````php
namespace Survos\OmekaBundle\Core\Module;

class ModuleManager
{
   public function __construct(
       private TenantContext $tenantContext,
       private Connection $connection,
       private ContainerInterface $container,
   ) {}
   
   public function isEnabled(string $bundleName): bool
   {
       $tenantId = $this->tenantContext->getCurrentTenantId();
       
       $enabled = $this->connection->fetchOne(
           'SELECT enabled FROM module_activation WHERE tenant_id = ? AND bundle_name = ?',
           [$tenantId, $bundleName]
       );
       
       return (bool) $enabled;
   }
   
   public function getConfig(string $bundleName, string $key, mixed $default = null): mixed
   {
       $tenantId = $this->tenantContext->getCurrentTenantId();
       
       $value = $this->connection->fetchOne(
           'SELECT config_value FROM module_config 
            WHERE tenant_id = ? AND bundle_name = ? AND config_key = ?',
           [$tenantId, $bundleName, $key]
       );
       
       return $value ? json_decode($value, true) : $default;
   }
}
````

## Migration Path for Existing Omeka-S Sites

### Step-by-Step Migration

1. **Install Core Bundle**
````bash
  composer require survos-omeka/core-bundle
  bin/console omeka:migrate:prepare
````

2. **Migrate Data Schema**
````bash
  # Analyzes existing Omeka-S database
  # Generates Doctrine migrations for Symfony
  bin/console omeka:migrate:schema
  bin/console doctrine:migrations:migrate
````

3. **Convert Modules One-by-One**
````bash
  # Replace Omeka-S module with bundle
  composer require survos-omeka/value-suggest-bundle
  bin/console omeka:migrate:module ValueSuggest
  
  # Migrates module config from .ini to database
  # Updates activation records
````

4. **Test & Verify**
````bash
  bin/console omeka:verify:data
  bin/console omeka:verify:modules
````

5. **Remove Omeka-S Core** (eventual goal)
````bash
  composer remove omeka/omeka-s
  # Site now runs on pure Symfony
````

## Priority Modules to Convert

### Phase 1: Foundation (High Priority)
1. **ValueSuggest** - Simple, popular, well-defined
2. **CustomVocab** - Fundamental functionality
3. **NumericDataTypes** - Common, straightforward
4. **Mapping** - Geographic features, known codebase

### Phase 2: Enhanced Features
5. **IIIF Server** - Image serving, important for museums
6. **FileSideload** - Bulk import capabilities
7. **CSVImport** - Data migration essential
8. **UniversalViewer** - Popular viewer integration

### Phase 3: Showcase (Survos Originals)
9. **MeiliSearchBundle** - Hybrid semantic search
10. **ScanStationBundle** - AI-powered digitization
11. **MuseadoBundle** - Multilingual aggregation

## Benefits Over Traditional Omeka-S

### For Developers
- ✅ Modern Symfony patterns and best practices
- ✅ Full IDE autocomplete and static analysis
- ✅ Standard Composer dependency management
- ✅ PHPUnit testing without Omeka quirks
- ✅ Symfony profiler and debugging tools
- ✅ No Laminas legacy code

### For Museums/Institutions
- ✅ Multi-tenant ready out of the box
- ✅ Better performance (Symfony optimization)
- ✅ Easier hosting (standard Symfony deployment)
- ✅ Professional ecosystem (Packagist, security advisories)
- ✅ Gradual migration path from Omeka-S
- ✅ Mix and match bundles as needed

### For the Community
- ✅ Lower barrier to entry (Symfony >> Laminas)
- ✅ Broader developer pool
- ✅ Better documentation ecosystem
- ✅ Modern CI/CD and testing practices
- ✅ Active Symfony community support

## Marketing & Positioning

### Tagline Options
- "Omeka-S, reimagined for Symfony"
- "Modern museum collections for the Symfony era"
- "Cultural heritage meets modern PHP"
- "All the Omeka functionality, none of the Laminas baggage"

### Target Audience
1. **Existing Omeka-S users** looking to modernize
2. **Symfony developers** entering museum/GLAM space
3. **Museums** wanting multi-tenant SaaS solutions
4. **Digital humanities** projects needing flexibility
5. **Survos clients** (natural fit for existing work)

### Differentiation
- Not a fork - a clean reimplementation
- Maintains Omeka concepts and workflows
- Multi-tenant first-class citizen
- Modern Symfony throughout
- Commercial support available (Survos)

## Implementation Roadmap

### Phase 1: Foundation (3-4 months)
- [ ] Create `survos-omeka/core-bundle` with basic infrastructure
- [ ] Multi-tenant connection manager
- [ ] Module activation system
- [ ] Basic entity layer (Item, ItemSet, Media)
- [ ] Admin UI framework
- [ ] Security/ACL system

### Phase 2: Conversion Tooling (2 months)
- [ ] Build `omeka:bundle-create` command
- [ ] AST parser for Laminas patterns
- [ ] Code generator for Symfony equivalents
- [ ] Template conversion (PHTML → Twig)
- [ ] Documentation generator

### Phase 3: First Bundles (3-4 months)
- [ ] Convert ValueSuggest
- [ ] Convert CustomVocab
- [ ] Convert NumericDataTypes
- [ ] Convert Mapping
- [ ] Publish to Packagist
- [ ] Write bundle documentation

### Phase 4: Migration Tools (2 months)
- [ ] Data migration commands
- [ ] Config migration tools
- [ ] Verification/testing tools
- [ ] Migration documentation

### Phase 5: Showcase & Launch (2-3 months)
- [ ] MeiliSearchBundle (original work)
- [ ] Demo site with sample data
- [ ] Comprehensive documentation
- [ ] Tutorial videos
- [ ] Blog posts and marketing
- [ ] Conference presentation (SymfonyCon?)

## Technical Notes

### Converter Implementation Sketch
````php
namespace Survos\OmekaBundle\Dev\Command;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use PhpParser\ParserFactory;

class BundleCreateCommand extends Command
{
   private array $patterns = [];
   
   public function convert(string $modulePath, string $outputDir): void
   {
       // 1. Parse module structure
       $module = $this->parseModule($modulePath);
       
       // 2. Extract and convert patterns
       $events = $this->convertEvents($module);
       $services = $this->convertServices($module);
       $routes = $this->convertRoutes($module);
       $controllers = $this->convertControllers($module);
       
       // 3. Generate bundle structure
       $this->generateBundleClass($outputDir, $module);
       $this->generateExtension($outputDir, $services);
       $this->generateEventSubscribers($outputDir, $events);
       $this->generateControllers($outputDir, $controllers, $routes);
       $this->generateComposerJson($outputDir, $module);
       $this->generateReadme($outputDir, $module);
       
       // 4. Copy and convert views
       $this->convertViews($modulePath, $outputDir);
   }
   
   private function convertRoutes(array $module): array
   {
       $routes = [];
       $config = include $module['path'] . '/config/module.config.php';
       
       foreach ($config['router']['routes'] ?? [] as $name => $route) {
           $routes[] = [
               'name' => $name,
               'path' => $route['options']['route'],
               'controller' => $route['options']['defaults']['controller'],
               'action' => $route['options']['defaults']['action'],
               'methods' => $route['options']['defaults']['methods'] ?? ['GET'],
           ];
       }
       
       return $routes;
   }
   
   private function convertControllers(array $module): array
   {
       $parser = (new ParserFactory())->createForNewestSupportedVersion();
       $controllers = [];
       
       foreach (glob($module['path'] . '/src/Controller/*.php') as $file) {
           $ast = $parser->parse(file_get_contents($file));
           
           // Extract controller class and methods
           // Convert action methods to Symfony controller actions
           // Add route attributes
           
           $controllers[] = $this->generateSymfonyController($ast);
       }
       
       return $controllers;
   }
}
````

### Key Dependencies
````json
{
   "require": {
       "nikic/php-parser": "^5.0",
       "nette/php-generator": "^4.1",
       "symfony/filesystem": "^7.0"
   }
}
````

## Next Steps

1. **Create GitHub Organization**: `survos-omeka` or use existing `survos` org
2. **Bootstrap core-bundle**: Basic structure, multi-tenant foundation
3. **Build proof-of-concept converter**: Simple module (ValueSuggest) end-to-end
4. **Document patterns**: Create conversion guide for contributors
5. **Community outreach**: Gauge interest from Omeka and Symfony communities
6. **Conference proposal**: SymfonyCon or DLF Forum presentation

## Related Projects & Resources

- **Omeka-S**: https://github.com/omeka/omeka-s
- **Omeka-S Modules**: https://github.com/omeka-s-modules
- **Symfony Best Practices**: https://symfony.com/doc/current/best_practices.html
- **nikic/php-parser**: https://github.com/nikic/PHP-Parser
- **nette/php-generator**: https://github.com/nette/php-generator

## Jobs and Tasks Redesign

- Tasks are migrating into Symfony console commands for CLI and queued execution.
- Jobs remain tracking records; they no longer execute logic directly.
- Messenger handlers run the commands and update job status/progress.
- Full details: `doc/jobs-and-tasks.md`.

## Contact & Collaboration

**Tac Tacelosky**
- Email: tac@survos.com
- GitHub: @tacman
- Company: Survos (https://survos.com)
- Foundation: Museado Foundation

**Looking for:**
- Symfony developers interested in GLAM sector
- Museum technologists wanting modern infrastructure
- Contributors for module conversion
- Beta testers with Omeka-S sites
- Feedback from Symfony and Omeka communities
