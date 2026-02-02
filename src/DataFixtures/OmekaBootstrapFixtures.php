<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Omeka\Entity\Migration;
use Omeka\Entity\Setting;
use Omeka\Module as OmekaModule;
use Omeka\Entity\Module;

class OmekaBootstrapFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->ensureCoreModuleLoaded();
        $this->ensureVersionSetting($manager);
        $this->ensureModuleRecord($manager);
        $this->ensureMigrations($manager);
        $manager->flush();
    }

    private function ensureCoreModuleLoaded(): void
    {
        if (!defined('OMEKA_PATH')) {
            define('OMEKA_PATH', dirname(__DIR__, 2));
        }
        if (!class_exists(OmekaModule::class)) {
            require_once OMEKA_PATH . '/application/Module.php';
        }
    }

    private function ensureVersionSetting(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(Setting::class);
        $setting = $repository->find('version');
        if ($setting) {
            return;
        }

        $setting = new Setting();
        $setting->setId('version');
        $setting->setValue(OmekaModule::VERSION);
        $manager->persist($setting);
    }

    private function ensureModuleRecord(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(Module::class);
        $module = $repository->find('Omeka');
        if ($module) {
            return;
        }

        $module = new Module();
        $module->setId('Omeka');
        $module->setIsActive(true);
        $module->setVersion(OmekaModule::VERSION);
        $manager->persist($module);
    }

    private function ensureMigrations(ObjectManager $manager): void
    {
        $migrationsDir = OMEKA_PATH . '/application/data/migrations';
        $files = glob($migrationsDir . '/*.php') ?: [];
        if (!$files) {
            return;
        }

        $repository = $manager->getRepository(Migration::class);
        foreach ($files as $file) {
            $baseName = basename($file);
            if (!preg_match('/^(\d+)_/', $baseName, $matches)) {
                continue;
            }

            $version = $matches[1];
            if ($repository->find($version)) {
                continue;
            }

            $migration = new Migration();
            $migration->setVersion($version);
            $manager->persist($migration);
        }
    }
}
