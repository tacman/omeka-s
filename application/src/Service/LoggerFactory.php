<?php
namespace Omeka\Service;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Omeka\Log\Processor\PsrPlaceholder;

/**
 * Logger factory.
 */
class LoggerFactory implements FactoryInterface
{
    /**
     * Create the logger service.
     *
     * @return Logger
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        $config = $serviceLocator->get('Config');
        $level = Logger::toMonologLevel($config['logger']['priority'] ?? Logger::NOTICE);
        $logger = new Logger('omeka');
        if (isset($config['logger']['log'])
            && $config['logger']['log']
            && isset($config['logger']['path'])
        ) {
            try {
                $handler = new StreamHandler($config['logger']['path'], $level);
                $handler->setFormatter(new LineFormatter('%datetime% %level_name% (%level%): %message%' . "\n"));
            } catch (\Throwable $e) {
                $handler = new NullHandler;
                error_log('Omeka S log initialization failed: ' . $e->getMessage());
            }
        } else {
            $handler = new NullHandler;
        }
        $logger->pushHandler($handler);
        $logger->pushProcessor(new PsrPlaceholder());
        return $logger;
    }
}
