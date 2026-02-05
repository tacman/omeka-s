<?php

declare(strict_types=1);

namespace App\Model;

final class BrowseResult
{
    public function __construct(
        private readonly string $resourceType,
        private readonly array $resources,
        private readonly array $columns,
        private readonly int $total,
        private readonly array $query,
    ) {
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getQuery(): array
    {
        return $this->query;
    }
}
