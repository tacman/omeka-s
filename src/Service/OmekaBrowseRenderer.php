<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeInterface;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\AbstractRepresentation;

final class OmekaBrowseRenderer
{
    public function __construct(private readonly LegacyUrlGenerator $legacyUrlGenerator)
    {
    }

    public function renderHeader(array $columnData): string
    {
        $type = $columnData['type'] ?? '';
        $header = $columnData['header'] ?? null;
        if (is_string($header) && $header !== '') {
            return $header;
        }

        return match ($type) {
            'id' => 'ID',
            'slug' => 'URL slug',
            'owner' => 'Owner',
            'created' => 'Created',
            'modified' => 'Modified',
            'resource_class' => 'Resource class',
            'resource_template' => 'Resource template',
            'is_public' => 'Public',
            'is_open' => 'Open',
            default => $type,
        };
    }

    public function renderTitle(AbstractEntityRepresentation $resource): string
    {
        foreach (['displayTitle', 'title', 'label', 'name'] as $method) {
            if (method_exists($resource, $method)) {
                $value = $resource->$method();
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        if (method_exists($resource, 'id')) {
            return (string) $resource->id();
        }

        return '';
    }

    public function renderContent(AbstractEntityRepresentation $resource, array $columnData): string
    {
        $type = $columnData['type'] ?? '';

        return match ($type) {
            'owner' => $this->renderOwner($resource),
            'resource_class' => $this->renderResourceClass($resource),
            'resource_template' => $this->renderResourceTemplate($resource),
            'is_public' => $this->renderBool($resource, 'isPublic'),
            'is_open' => $this->renderBool($resource, 'isOpen'),
            default => $this->renderFromMethod($resource, $type, $columnData['default'] ?? ''),
        };
    }

    private function renderFromMethod(AbstractEntityRepresentation $resource, string $type, string $default): string
    {
        $method = $this->mapTypeToMethod($type);
        if (!method_exists($resource, $method)) {
            return $default;
        }

        $value = $resource->$method();

        return $this->formatValue($value, $default);
    }

    private function mapTypeToMethod(string $type): string
    {
        return match ($type) {
            'resource_class' => 'resourceClass',
            'resource_template' => 'resourceTemplate',
            'is_public' => 'isPublic',
            'is_open' => 'isOpen',
            'media_type' => 'mediaType',
            default => $type,
        };
    }

    private function formatValue(mixed $value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if ($value instanceof AbstractRepresentation) {
            foreach (['label', 'name', 'title'] as $method) {
                if (method_exists($value, $method)) {
                    return (string) $value->$method();
                }
            }
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $default;
    }

    private function renderOwner(AbstractEntityRepresentation $resource): string
    {
        if (!method_exists($resource, 'owner')) {
            return '';
        }

        $owner = $resource->owner();
        if (!$owner) {
            return '';
        }

        $name = method_exists($owner, 'name') ? $owner->name() : $owner->email();

        return (string) $name;
    }

    private function renderResourceClass(AbstractEntityRepresentation $resource): string
    {
        if (!method_exists($resource, 'resourceClass')) {
            return '';
        }

        $class = $resource->resourceClass();
        if (!$class) {
            return '';
        }

        return (string) $class->label();
    }

    private function renderResourceTemplate(AbstractEntityRepresentation $resource): string
    {
        if (!method_exists($resource, 'resourceTemplate')) {
            return '';
        }

        $template = $resource->resourceTemplate();
        if (!$template) {
            return '';
        }

        return (string) $template->label();
    }

    private function renderBool(AbstractEntityRepresentation $resource, string $method): string
    {
        if (!method_exists($resource, $method)) {
            return '';
        }

        return $resource->$method() ? 'Yes' : 'No';
    }
}
