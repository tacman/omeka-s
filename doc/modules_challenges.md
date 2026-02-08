# Why installing all popular Omeka-S modules in multi-tenant is perilous

**A multi-tenant Omeka-S deployment using PostgreSQL schemas faces fundamental architectural incompatibilities that go far beyond typical module management concerns.** Omeka-S was designed as a single-tenant MySQL application with no table prefix support, no PostgreSQL compatibility, and a module system that executes raw MySQL DDL for every install and upgrade. Every popular module — from BulkImport to Mapping to NumericDataTypes — hard-codes MySQL-specific SQL. The proposed architecture requires not just managing module conflicts, but effectively forking or wrapping every module's database layer. Combined with a module loading system that parses all installed module files on every request, a single-developer dependency for the most critical third-party modules, and a shared background job queue with no tenant isolation, this deployment pattern carries severe engineering and operational risk.

The Omeka-S ecosystem splits between **48 official modules** from the Corporation for Digital Scholarship and **60+ third-party modules** from Daniel Berthereau (Daniel-KM), with the latter providing much of the advanced functionality institutions actually need (bulk import, IIIF, advanced search, image tiling). The current stable release is **Omeka S 4.2.x**, and the 3.x→4.x transition broke most modules and remains the single largest source of compatibility issues.

---

## Every request loads every Module.php, even disabled ones

The most surprising performance finding is that **Omeka-S executes `require_once` on every Module.php file from every module directory on every HTTP request**, regardless of whether the module is active, inactive, or not even installed in the database. This behavior is confirmed by GitHub issue #1710 on the Omeka-S repository. The `ModuleManagerFactory` scans the `/modules` directory and loads each Module.php to validate that it contains a proper class extending `AbstractModule`. This has both a security implication (the loaded file has access to `$connection` and `$serviceLocator` variables) and a performance cost that scales linearly with installed module count.

For active modules, the overhead is substantially greater. Each active module triggers `getConfig()` (which includes `module.config.php`), `onBootstrap()`, and `attachListeners()` on every request. These configuration arrays are deeply merged into the application config. **Omeka-S does not enable Laminas configuration caching by default**, meaning this merge operation repeats on every page load. With 20+ active modules, each registering services, event listeners, form elements, view helpers, routes, and Doctrine entity mappings, the cumulative bootstrap overhead becomes significant.

The event listener system compounds this. Each active module attaches listeners to shared events like `api.create.post`, `view.show.after`, and `api.search.query`. Every time these events fire, all attached listeners dispatch in O(n) complexity. The template path stack also grows with each module — template resolution involves filesystem `stat()` calls against every registered view directory. Inactive modules avoid all of this overhead (their config is not merged, listeners not attached, assets not loaded), but **the only way to prevent the Module.php parsing is to physically remove module directories from disk**, not merely deactivate them.

For a multi-tenant deployment with selective per-tenant activation, this means you cannot simply have all modules installed on disk and toggle them in database config. The Module.php loading penalty applies regardless. Mitigation requires optimized Composer autoloading (`--classmap-authoritative`), proper opcache configuration (adequate `max_accelerated_files` and `memory_consumption`), and ideally a custom patch to Omeka-S's module discovery that skips `require_once` for directories not in the active list.

---

## PostgreSQL and schema-based multi-tenancy hit a wall of raw MySQL

The proposed PostgreSQL schema-per-tenant architecture confronts a fundamental reality: **Omeka-S requires MySQL/MariaDB and has zero PostgreSQL support**. This is not a soft preference — the database configuration only accepts MySQL parameters, and the official documentation states that "Omeka S must have a dedicated database; you cannot use a prefix." An Omeka core developer confirmed in 2018 that Doctrine's table prefix support was never implemented because "there may be some issues with enforcing the prefix on tables created by modules."

Every module's `install()` and `upgrade()` methods contain hand-written MySQL DDL. The patterns that break on PostgreSQL are pervasive:

- `AUTO_INCREMENT` (PostgreSQL uses `SERIAL` or `GENERATED ALWAYS AS IDENTITY`)
- `ENGINE = InnoDB` (no storage engine concept in PostgreSQL)
- `LONGTEXT` (PostgreSQL uses `TEXT`)
- `INT UNSIGNED` (not supported; requires `BIGINT` or `CHECK` constraints)
- Backtick quoting (PostgreSQL uses double quotes)
- `SET FOREIGN_KEY_CHECKS = 0` (PostgreSQL uses deferred constraints or `session_replication_role`)
- `SHOW INDEX FROM table` (must query `pg_indexes` catalog)
- `INSERT IGNORE` (PostgreSQL uses `ON CONFLICT DO NOTHING`)

The Mapping module is especially problematic, using MySQL's `GEOMETRY` spatial type with `ST_PointFromText()` — requiring PostGIS and completely rewritten spatial SQL. Daniel-KM's modules frequently use raw SQL at runtime (not just during installation), including MySQL-specific syntax like `SHOW INDEX` in the Search module's internal SQL adapter, which explicitly documents its comparisons as "mysql comparisons."

**No one has attempted true database-level multi-tenancy with Omeka-S.** The closest effort is the Teams module from the University of Illinois (documented in Code4Lib Journal #57, 2023), which provides application-level tenancy through access control filtering, not schema isolation. A schema-per-tenant approach would require a complete SQL translation layer for every module's install, upgrade, and runtime operations, plus a request-scoped `search_path` setter and schema-qualified foreign key references throughout.

Custom tables created by popular modules are extensive: Mapping creates `mapping` and `mapping_feature`; NumericDataTypes creates four tables (`_timestamp`, `_integer`, `_duration`, `_interval`); BulkImport creates `bulk_import`, `bulk_importer`, `bulk_imported`, `bulk_mapping`; Collecting creates five tables; CSVImport creates two tables. All reference core tables by bare name with foreign keys.

---

## Module conflicts cluster around three fault lines

Known conflicts between popular modules concentrate in three areas: **the 3.x→4.x version boundary**, **Daniel-KM's Common module dependency chain**, and **overlapping functionality between official and third-party modules**.

The Omeka S 4.0.0 release introduced breaking API changes (new resource page blocks, column configuration) that required updates from virtually every module. Forum posts document widespread breakage — blank pages, "Site Under Maintenance" errors, `TableExistsException` during Mapping v1.x→v2.x upgrades. Any module not explicitly updated for 4.x will fail.

The Common module (v3.4.77, February 2026) is a **single point of failure for the entire Daniel-KM ecosystem**. Nearly all of his modules — BulkImport, BulkExport, IIIF Server, ImageServer, Search, SearchSolr, Guest, AdvancedResourceTemplate, and others — require it. A version mismatch between Common and any dependent module produces cascading failures (the most common is "Call to a member function getDb() on bool"). The module uses a PHP Trait pattern (`Common\TraitModule`) where `parent::method()` behaves differently than with abstract class inheritance, a documented source of subtle bugs.

Specific documented conflicts include:

- **BulkImport + NumericDataTypes**: Language tags silently dropped for html, xml, and numeric data types during import. A Doctrine cascade persist bug caused `ORMInvalidArgumentException` when bulk-creating resources with numeric values (fixed in NumericDataTypes 4.1 via Daniel-KM's PR #29)
- **Search (BibLibre) vs. Search (Daniel-KM)**: Two different "Search" modules exist with confusing naming. BibLibre's original is deprecated; Daniel-KM's "Advanced Search" is actively maintained. Installing both causes conflicts
- **Solr (BibLibre) vs. SearchSolr (Daniel-KM)**: Same duplication problem; they use different libraries (PECL Solr extension vs. Solarium PHP library) and are incompatible
- **CSVImport (official) vs. BulkImport**: Functionally overlapping with different data models. CSVImport has 16 open issues and 6 unmerged PRs; BulkImport is more powerful but more complex
- **IIIF Server + ImageServer + UniversalViewer**: Version dependency chain requires specific upgrade order (UV to 3.4.3 before enabling ImageServer) or old options are silently lost

---

## Maintenance risk concentrates in one developer and several stagnant modules

The health of the Omeka-S module ecosystem depends disproportionately on a single individual. **Daniel Berthereau maintains 60+ modules** that provide the most-used advanced functionality: bulk import/export, IIIF support, image tiling, advanced search, Solr integration, guest users, and advanced resource templates. Every one of these modules carries the disclaimer "Use it at your own risk." If this developer becomes unavailable, a large portion of the ecosystem's critical functionality would be orphaned.

Among official Omeka Team modules, **CSVImport** (16 open issues, 6 open PRs) shows signs of neglect relative to its third-party replacement. **MetadataBrowse** was last updated in December 2022. **Scripto** saw its last release in June 2024 and requires a separate MediaWiki installation that introduces its own maintenance burden.

Third-party modules with concerning maintenance status include **RestrictedSites** (last updated June 2023, and critically, it does not restrict API access — content on "restricted" sites remains fully visible via the API), **Cartography** (last updated January 2024, with a complex dependency chain through Annotate and DataTypeGeometry), and **ItemSetsTree** from BibLibre (largely superseded by the official Hierarchy module).

For the multi-tenant architecture specifically, the module quality concern is amplified: **every bug affects all tenants simultaneously**. A faulty update to Common that breaks BulkImport would disable import functionality across every tenant in the deployment.

---

## Multi-tenant resource isolation has deep structural gaps

Beyond database schema issues, Omeka-S modules assume a shared-everything model that creates tenant isolation problems at multiple levels.

**Background jobs** are the most critical gap. All imports, exports, image tiling operations, and search reindexing share a single global job queue. A tenant running a large BulkImport (which spawns separate sub-jobs per item to avoid PHP timeouts) can monopolize the job queue and delay operations for all other tenants. There is no per-tenant throttling, priority, or resource limiting. The CLI task runner (`EasyAdmin/data/scripts/task.php`) has no tenant scoping — tasks execute in global context.

**File system resources** are entirely shared. The `files/` directory tree (originals, thumbnails, tiles, temporary import files, sideload directories) has no per-tenant partitioning. ImageServer's tiling operation can consume **2-5x the original image size** in disk space and creates significant CPU spikes during upload. One tenant uploading a large image collection degrades performance for all others. FileSideload's configured directory is a single path — all tenants share access or need separate module configurations, which is not possible in a single installation.

**External service connections** are shared globally: Solr cores (though SearchSolr does support multi-install sharing via an `index_id` field), VIAF/Library of Congress/Getty API rate limits, MediaWiki instances for Scripto, and map tile provider API keys. ValueSuggest makes live HTTP requests to external vocabulary services with no caching layer — multiple tenants searching simultaneously can hit rate limits.

**Module data tables lack tenant scoping.** The `mapping` and `mapping_feature` tables key data only to `item_id`. CustomVocab vocabularies are global. NumericDataTypes tables reference `resource_id` globally. The `module` table itself — which tracks installed versions and active status — is a single global table with no per-tenant state management. This means all tenants must run identical module versions and cannot have different modules active without a custom middleware layer that intercepts module state queries per-tenant.

---

## Converting to Symfony bundles demands rebuilding, not porting

Analysis of the three modules targeted for Symfony conversion — BulkImport, ValueSuggest, and Mapping — reveals that **direct conversion is impractical for two of three; only ValueSuggest is a reasonable port candidate**.

**ValueSuggest is the easiest conversion** and should be first. It creates no database tables, stores values in Omeka's core `value` table, and consists primarily of HTTP client classes (Suggesters) that query external vocabulary APIs. The Suggester classes are pure services with a single `getSuggestions($query, $lang)` method — trivially portable to Symfony services tagged for auto-discovery. The jQuery Autocomplete frontend is framework-agnostic. The main challenge is rebuilding the DataType integration with the resource editing UI.

**Mapping requires a rebuild, not conversion.** It creates custom Doctrine entities with foreign key relationships to Omeka core entities (`Item`, `Media`), uses MySQL spatial types requiring PostGIS equivalents, hooks into Omeka's API adapter lifecycle for hydrating mapping data alongside item CRUD, and depends on Doctrine visibility filters for public/private content. The Leaflet.js frontend is reusable, and the `LongitudeOne\Spatial` Doctrine extension works in Symfony, but the PHP backend must be redesigned around a new service layer.

**BulkImport should be rebuilt from scratch.** It is effectively a mini-application: a Reader→Mapper→Processor pipeline architecture, its own extensibility framework with plugin types, Twig-based mapping transformations, CodeMirror configuration editors, complex background job orchestration, and deep integration with Omeka's API Manager for resource creation. The module has ~1000+ lines in Module.php alone with dozens of event listeners. Converting line-by-line would cost more than designing a clean Symfony-native import system using Symfony Messenger for job queuing and tagged services for the plugin architecture.

Key architectural patterns that resist conversion include Omeka's **SharedEventManager dual-key event system** (no Symfony equivalent — requires composite event naming or custom wrapper), the **API adapter hydration/representation pattern** (unique to Omeka, must be designed from scratch), **raw SQL migrations** (must be replaced with Doctrine Migrations Bundle), and **Laminas plugin managers** (specialized sub-containers that become tagged services with compiler passes in Symfony). The ServiceLocator anti-pattern pervades all modules — every `$services->get('ServiceName')` call must become constructor injection.

No prior Omeka-S to Symfony migration exists anywhere. The closest precedent is VuFind's successful migration of CLI tools from `laminas-console` to `symfony/console` while keeping the rest on Laminas — supporting a "Strangler Fig" incremental approach rather than a full rewrite.

---

## Conclusion

This multi-tenant architecture faces three categories of risk, in descending order of severity. **First, the PostgreSQL requirement is architecturally incompatible with Omeka-S's entire module ecosystem** — every module writes raw MySQL DDL, and a translation layer for 20+ modules' install, upgrade, and runtime SQL is a major engineering project with ongoing maintenance cost as modules update. **Second, true tenant isolation does not exist** at the module level — shared job queues, file systems, external service connections, and global data tables mean that operational problems in one tenant propagate to all others. **Third, the single-developer dependency for critical modules** (BulkImport, IIIF Server, ImageServer, Search, SearchSolr) creates supply-chain risk that compounds in a multi-tenant deployment where failures affect all tenants simultaneously.

The most viable path forward treats Omeka-S's module code as a **reference implementation rather than a migration source**. Build the Symfony bundles as clean-room implementations informed by Omeka module behavior, starting with ValueSuggest (simplest, no database), then Mapping (medium complexity, reusable frontend), and finally a new import/export system inspired by but not derived from BulkImport. For the interim period, running Omeka-S in its native MySQL single-tenant configuration alongside a gradually growing Symfony application — using the Strangler Fig pattern — avoids the most dangerous incompatibilities while preserving access to the existing module ecosystem.