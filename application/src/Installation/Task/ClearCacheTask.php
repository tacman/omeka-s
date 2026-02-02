<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;

/**
 * Task to clear Doctrine's metadata cache.
 */
class ClearCacheTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $em = $installer->getServiceLocator()->get('Omeka\EntityManager');
        $config = $em->getConfiguration();
        if (method_exists($config, 'getMetadataCache')) {
            $cache = $config->getMetadataCache();
            if ($cache) {
                $cache->clear();
            }
            return;
        }

        if (method_exists($config, 'getMetadataCacheImpl')) {
            $cache = $config->getMetadataCacheImpl();
            if ($cache) {
                $cache->deleteAll();
            }
        }
    }
}
