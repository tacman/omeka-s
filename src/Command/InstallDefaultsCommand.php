<?php

namespace App\Command;

use App\Command\Input\TenantInput;
use App\Tenant\TenantContext;
use Omeka\Entity\User;
use Omeka\Installation\Installer;
use Omeka\Installation\Task\InstallDefaultTemplatesTask;
use Omeka\Installation\Task\InstallDefaultVocabulariesTask;
use Omeka\Mvc\Application as OmekaApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:install-defaults',
    description: 'Install default Omeka vocabularies and templates',
)]
class InstallDefaultsCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[MapInput] TenantInput $tenantInput,
        TenantContext $tenantContext,
        #[Option('Skip installing default vocabularies')]
        bool $skipVocabularies = false,
        #[Option('Skip installing default resource templates')]
        bool $skipTemplates = false,
        #[Option('Admin email to run as')]
        string $adminEmail = 'admin@example.com',
    ): int {
        if ($tenantInput->tenantCode !== null) {
            $tenantContext->setTenantCode($tenantInput->tenantCode);
        }

        $projectRoot = dirname(__DIR__, 2);
        $bootstrapPath = $projectRoot . '/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        } elseif (!defined('OMEKA_PATH')) {
            define('OMEKA_PATH', $projectRoot);
        }

        if ($skipVocabularies && $skipTemplates) {
            $io->warning('Nothing to do. Both vocabularies and templates were skipped.');
            return Command::SUCCESS;
        }

        $config = require OMEKA_PATH . '/application/config/application.config.php';
        $application = OmekaApplication::init($config);
        $services = $application->getServiceManager();

        $entityManager = $services->get('Omeka\EntityManager');
        $adminUser = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $adminEmail]);
        if (!$adminUser) {
            $io->error(sprintf('Admin user not found for email: %s', $adminEmail));
            return Command::FAILURE;
        }

        $auth = $services->get('Omeka\AuthenticationService');
        $auth->getStorage()->write($adminUser);

        $installer = new Installer($services);

        try {
            if (!$skipVocabularies) {
                (new InstallDefaultVocabulariesTask())->perform($installer);
                if ($installer->getErrors()) {
                    $this->renderErrors($io, $installer->getErrors(), 'vocabularies');
                    return Command::FAILURE;
                }
                $io->success('Default vocabularies installed.');
            }

            if (!$skipTemplates) {
                (new InstallDefaultTemplatesTask())->perform($installer);
                if ($installer->getErrors()) {
                    $this->renderErrors($io, $installer->getErrors(), 'templates');
                    return Command::FAILURE;
                }
                $io->success('Default resource templates installed.');
            }
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param string[] $errors
     */
    private function renderErrors(SymfonyStyle $io, array $errors, string $label): void
    {
        $io->error(sprintf('Failed installing %s.', $label));
        foreach ($errors as $error) {
            $io->writeln($error);
        }
    }
}
