<?php

declare(strict_types=1);

namespace App\Security;

use Omeka\Entity\User as OmekaUserEntity;
use Omeka\Permissions\Acl;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class OmekaUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private readonly OmekaUserEntity $user)
    {
    }

    public function getOmekaUser(): OmekaUserEntity
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->user->getEmail();
    }

    public function getPassword(): ?string
    {
        return $this->user->getPasswordHash();
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        $role = (string) $this->user->getRole();
        if ($role !== '') {
            $roles[] = 'ROLE_' . strtoupper($role);
        }

        if (in_array($role, [Acl::ROLE_GLOBAL_ADMIN, Acl::ROLE_SITE_ADMIN], true)) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }
}
