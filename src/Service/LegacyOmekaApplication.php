<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;
use Omeka\Mvc\Application as OmekaApplication;

final class LegacyOmekaApplication
{
    private ?Application $application = null;

    public function __construct(private readonly string $projectDir)
    {
    }

    public function getApplication(): Application
    {
        if ($this->application) {
            return $this->application;
        }

        if (!defined('OMEKA_PATH')) {
            define('OMEKA_PATH', $this->projectDir);
        }

        $configPath = $this->projectDir . '/application/config/application.config.php';
        $this->application = OmekaApplication::init(require $configPath);

        return $this->application;
    }

    public function getServiceManager(): ServiceManager
    {
        return $this->getApplication()->getServiceManager();
    }
}
