<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Form\FormInterface;

final class LegacyViewRenderer
{
    private ?PhpRenderer $renderer = null;

    public function __construct(private readonly LegacyOmekaApplication $legacyApp)
    {
    }

    public function render(string $template, array $variables = []): string
    {
        $model = new ViewModel($variables);
        $model->setTemplate($template);

        return $this->getRenderer()->render($model);
    }

    public function getRenderer(): PhpRenderer
    {
        if ($this->renderer) {
            return $this->renderer;
        }

        $this->renderer = $this->legacyApp->getServiceManager()->get('ViewRenderer');

        return $this->renderer;
    }

    public function formOpen(FormInterface $form): string
    {
        return $this->getRenderer()->form()->openTag($form);
    }

    public function formClose(): string
    {
        return $this->getRenderer()->form()->closeTag();
    }

    public function formRow(mixed $element): string
    {
        return $this->getRenderer()->formRow($element);
    }

    public function sectionNav(array $sections, string $event): string
    {
        return $this->getRenderer()->sectionNav($sections, $event);
    }

    public function cancelButton(): string
    {
        return $this->getRenderer()->cancelButton();
    }

    public function hyperlink(string $label, string $url, array $attributes = []): string
    {
        return $this->getRenderer()->hyperlink($label, $url, $attributes);
    }
}
