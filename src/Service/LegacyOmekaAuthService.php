<?php

declare(strict_types=1);

namespace App\Service;

use Omeka\Entity\User as OmekaUser;

final class LegacyOmekaAuthService
{
    public function __construct(private readonly LegacyOmekaApplication $legacyApp)
    {
    }

    public function ensureIdentity(): void
    {
        $services = $this->legacyApp->getServiceManager();
        $auth = $services->get('Omeka\\AuthenticationService');
        if ($auth->hasIdentity()) {
            return;
        }

        $email = getenv('OMEKA_FORCE_ADMIN_EMAIL') ?: 'admin@example.com';
        $entityManager = $services->get('Omeka\\EntityManager');
        $user = $entityManager->getRepository(OmekaUser::class)
            ->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $auth->getStorage()->write($user);
    }
}
