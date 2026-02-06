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
        private readonly array $sortOptions = [],
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

    public function getSortOptions(): array
    {
        return $this->sortOptions;
    }

    public function getCurrentPage(): int
    {
        return (int) ($this->query['page'] ?? 1);
    }

    public function getPerPage(): int
    {
        return (int) ($this->query['per_page'] ?? 25);
    }

    public function getTotalPages(): int
    {
        $perPage = $this->getPerPage();
        return $perPage > 0 ? (int) ceil($this->total / $perPage) : 1;
    }

    public function getSortBy(): string
    {
        return $this->query['sort_by'] ?? 'id';
    }

    public function getSortOrder(): string
    {
        return $this->query['sort_order'] ?? 'desc';
    }

    public function hasPreviousPage(): bool
    {
        return $this->getCurrentPage() > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->getCurrentPage() < $this->getTotalPages();
    }
}
