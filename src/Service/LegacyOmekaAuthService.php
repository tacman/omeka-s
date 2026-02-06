<?php

declare(strict_types=1);

namespace App\Service;

use Omeka\Entity\User as OmekaUser;
use Symfony\Bundle\SecurityBundle\Security;

final class LegacyOmekaAuthService
{
    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
        private readonly Security $security,
    ) {
    }

    public function ensureIdentity(): void
    {
        $services = $this->legacyApp->getServiceManager();
        $auth = $services->get('Omeka\\AuthenticationService');

        if (!$auth->hasIdentity()) {
            // Use the Symfony-authenticated user's email if available,
            // falling back to env var or default for CLI/testing contexts.
            $symfonyUser = $this->security->getUser();
            if ($symfonyUser) {
                $email = $symfonyUser->getUserIdentifier();
            } else {
                $email = getenv('OMEKA_FORCE_ADMIN_EMAIL') ?: 'admin@example.com';
            }

            $entityManager = $services->get('Omeka\\EntityManager');
            $user = $entityManager->getRepository(OmekaUser::class)
                ->findOneBy(['email' => $email]);
            if (!$user) {
                return;
            }

            $auth->getStorage()->write($user);
        }

        // Ensure UserSettings target ID is set so view helpers like
        // userSetting() work. Mirrors Omeka's MvcListeners::bootstrapLocale.
        $userId = $auth->getIdentity()->getId();
        $services->get('Omeka\Settings\User')->setTargetId($userId);
    }
}
