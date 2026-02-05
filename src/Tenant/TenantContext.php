<?php

declare(strict_types=1);

namespace App\Tenant;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

final class TenantContext
{
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
}
