<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\OmekaBrowseRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Symfony\Bridge\Twig\Attribute\AsTwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[AsTwigExtension]
final class OmekaBrowseExtension extends AbstractExtension
{
    public function __construct(private readonly OmekaBrowseRenderer $renderer)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('browse_header', [$this, 'header']),
            new TwigFunction('browse_cell', [$this, 'cell']),
            new TwigFunction('browse_title', [$this, 'title']),
        ];
    }

    public function header(array $columnData): string
    {
        return $this->renderer->renderHeader($columnData);
    }

    public function cell(AbstractEntityRepresentation $resource, array $columnData): string
    {
        return $this->renderer->renderContent($resource, $columnData);
    }

    public function title(AbstractEntityRepresentation $resource): string
    {
        return $this->renderer->renderTitle($resource);
    }
}
