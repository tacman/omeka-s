<?php
namespace Omeka\Service;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Omeka\Db\Event\Listener\ResourceDiscriminatorMap;
use Omeka\Db\Event\Subscriber\Entity;
use Omeka\Db\ProxyAutoloader;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Factory for creating the Doctrine entity manager.
 */
class EntityManagerFactory implements FactoryInterface
{
    const IS_DEV_MODE = false;

    /**
     * Create the entity manager service.
     *
     * @param ContainerInterface $serviceLocator
     * @return EntityManager
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        if (!class_exists(\Doctrine\Common\Proxy\AbstractProxyFactory::class, false)) {
            require_once OMEKA_PATH . '/application/data/overrides/AbstractProxyFactory.php';
        }
        if (!class_exists(\Doctrine\ORM\Proxy\ProxyFactory::class, false)) {
            require_once OMEKA_PATH . '/application/data/overrides/ProxyFactory.php';
        }

        $appConfig = $serviceLocator->get('ApplicationConfig');
        $config = $serviceLocator->get('Config');

        if (!isset($appConfig['connection'])) {
            throw new Exception\ConfigException('Missing database connection configuration');
        }
        if (!isset($config['entity_manager'])) {
            throw new Exception\ConfigException('Missing entity manager configuration');
        }
        if (!isset($config['entity_manager']['mapping_classes_paths'])) {
            throw new Exception\ConfigException('Missing mapping classes paths configuration');
        }
        if (!isset($config['entity_manager']['resource_discriminator_map'])) {
            throw new Exception\ConfigException('Missing resource discriminator map configuration');
        }
        if (isset($config['entity_manager']['is_dev_mode'])) {
            $isDevMode = (bool) $config['entity_manager']['is_dev_mode'];
        } else {
            $isDevMode = self::IS_DEV_MODE;
        }

        if (extension_loaded('apcu') && !$isDevMode && ApcuAdapter::isSupported()) {
            $cache = new ApcuAdapter('omeka_doctrine');
        } else {
            $cache = new ArrayAdapter();
        }

        // Set up the entity manager configuration.
        $emConfig = ORMSetup::createConfiguration(
            $isDevMode,
            OMEKA_PATH . '/application/data/doctrine-proxies',
            $cache
        );
        $useAttributeDriver = $this->hasAttributeMappings($config['entity_manager']['mapping_classes_paths']);
        if ($useAttributeDriver && class_exists(\Doctrine\ORM\Mapping\Driver\AttributeDriver::class)) {
            $driver = new \Doctrine\ORM\Mapping\Driver\AttributeDriver(
                $config['entity_manager']['mapping_classes_paths']
            );
            $emConfig->setMetadataDriverImpl($driver);
        } elseif (class_exists(\Doctrine\ORM\Mapping\Driver\AnnotationDriver::class)) {
            if (class_exists(\Doctrine\Common\Annotations\AnnotationRegistry::class)) {
                \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
            }
            $reader = new \Doctrine\Common\Annotations\AnnotationReader();
            $driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
                $reader,
                $config['entity_manager']['mapping_classes_paths']
            );
            $emConfig->setMetadataDriverImpl($driver);
        } elseif (class_exists(\Doctrine\ORM\Mapping\Driver\AttributeDriver::class)) {
            $driver = new \Doctrine\ORM\Mapping\Driver\AttributeDriver(
                $config['entity_manager']['mapping_classes_paths']
            );
            $emConfig->setMetadataDriverImpl($driver);
        } else {
            throw new Exception\ConfigException('Doctrine ORM metadata driver is missing. Install the ORM attribute or annotation driver.');
        }

        // Force non-persistent query cache, workaround for issue with SQL filters
        // that vary by user, permission level
        $emConfig->setQueryCache(new ArrayAdapter());

        // Use the underscore naming strategy to preempt potential compatibility
        // issues with the case sensitivity of various operating systems.
        // @see http://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html
        $emConfig->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));

        // Add SQL filters.
        foreach ($config['entity_manager']['filters'] as $name => $className) {
            $emConfig->addFilter($name, $className);
        }

        // Add custom data types.
        foreach ($config['entity_manager']['data_types'] as $name => $className) {
            if (!Type::hasType($name)) {
                Type::addType($name, $className);
            }
        }

        // Add custom functions.
        $emConfig->setCustomNumericFunctions($config['entity_manager']['functions']['numeric']);
        $emConfig->setCustomStringFunctions($config['entity_manager']['functions']['string']);
        $emConfig->setCustomDatetimeFunctions($config['entity_manager']['functions']['datetime']);

        // Load proxies from different directories
        $emConfig->setAutoGenerateProxyClasses($isDevMode);
        ProxyAutoloader::register($config['entity_manager']['proxy_paths'],
            $emConfig->getProxyNamespace());

        // Set up the entity manager.
        $connection = $serviceLocator->get('Omeka\Connection');
        $em = new EntityManager($connection, $emConfig);
        $em->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new ResourceDiscriminatorMap($config['entity_manager']['resource_discriminator_map'])
        );
        $em->getEventManager()->addEventSubscriber(new Entity($serviceLocator->get('EventManager')));

        // Instantiate the visibility filters and inject the service locator.
        $em->getFilters()->enable('resource_visibility');
        $em->getFilters()->getFilter('resource_visibility')->setServiceLocator($serviceLocator);
        $em->getFilters()->enable('value_visibility');
        $em->getFilters()->getFilter('value_visibility')->setServiceLocator($serviceLocator);
        $em->getFilters()->enable('site_page_visibility');
        $em->getFilters()->getFilter('site_page_visibility')->setServiceLocator($serviceLocator);

        return $em;
    }

    /**
     * @param string[] $paths
     */
    private function hasAttributeMappings(array $paths): bool
    {
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = @file_get_contents($file->getPathname());
                if ($contents === false) {
                    continue;
                }

                if (str_contains($contents, '#[ORM\\')
                    || str_contains($contents, '#[\\ORM\\')
                    || str_contains($contents, '#[Doctrine\\ORM\\Mapping\\')) {
                    return true;
                }
            }
        }

        return false;
    }
}
