<?php

declare(strict_types=1);

namespace App\Tenant;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

final class TenantContext
{
    private ?string $tenantCode = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getRepository(string $class): ObjectRepository
    {
        return $this->entityManager->getRepository($class);
    }

    public function setTenantCode(?string $tenantCode): void
    {
        $this->tenantCode = $tenantCode !== '' ? $tenantCode : null;
    }

    public function getTenantCode(): ?string
    {
        return $this->tenantCode;
    }
}
