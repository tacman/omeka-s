<?php
namespace Omeka\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * PSR-3 Logger factory.
 */
class PsrLoggerFactory implements FactoryInterface
{
    /**
     * Create the logger service.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return $serviceLocator->get('Omeka\Logger');
    }
}
