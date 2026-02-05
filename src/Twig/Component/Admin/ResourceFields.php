<?php

namespace App\Twig\Component\Admin;

use App\Service\LegacyViewRenderer;
use App\Service\OmekaAdminFormService;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\Form\FormInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AdminResourceFields', template: 'components/admin/ResourceFields.html.twig')]
final class ResourceFields
{
    public ?FormInterface $form = null;
    public ?AbstractResourceEntityRepresentation $resource = null;
    public string $action = 'edit';

    public function __construct(
        private readonly LegacyViewRenderer $legacyView,
        private readonly OmekaAdminFormService $adminFormService,
    ) {
    }

    public function mount(): void
    {
        if (!$this->form) {
            return;
        }
        $resourceTemplateSelect = $this->form->get('o:resource_template[o:id]');
        $resourceClassSelect = $this->form->get('o:resource_class[o:id]');
        $thumbnailInput = $this->form->get('o:thumbnail[o:id]');
        $ownerSelect = $this->form->get('o:owner[o:id]');

        if ($this->resource) {
            if ($resourceTemplate = $this->resource->resourceTemplate()) {
                $resourceTemplateSelect->setValue($resourceTemplate->id());
            }
            if ($resourceClass = $this->resource->resourceClass()) {
                $resourceClassSelect->setValue($resourceClass->id());
            }
            if ($thumbnail = $this->resource->thumbnail()) {
                $thumbnailInput->setValue($thumbnail->id());
            }
            if ($owner = $this->resource->owner()) {
                $ownerSelect->setValue($owner->id());
            }
        } else {
            $defaultTemplate = $this->legacyView->getRenderer()->userSetting('default_resource_template');
            $resourceTemplateSelect->setValue($defaultTemplate);
        }

        $this->setAutocomplete($resourceTemplateSelect);
        $this->setAutocomplete($resourceClassSelect);
    }

    public function resourceTemplateRow(): string
    {
        if (!$this->form) {
            return '';
        }
        $select = $this->form->get('o:resource_template[o:id]');
        return $select->getValueOptions() ? $this->legacyView->formRow($select) : '';
    }

    public function resourceClassRow(): string
    {
        if (!$this->form) {
            return '';
        }
        return $this->legacyView->formRow($this->form->get('o:resource_class[o:id]'));
    }

    public function thumbnailRow(): string
    {
        if (!$this->form) {
            return '';
        }
        return $this->legacyView->formRow($this->form->get('o:thumbnail[o:id]'));
    }

    public function ownerRow(): string
    {
        if (!$this->form) {
            return '';
        }
        return $this->legacyView->formRow($this->form->get('o:owner[o:id]'));
    }

    public function propertySelector(): string
    {
        return $this->legacyView->getRenderer()->propertySelector();
    }

    public function valueAnnotationSidebar(): string
    {
        return $this->legacyView->render('common/value-annotation-sidebar');
    }

    public function resourceSelectSidebar(): string
    {
        return $this->legacyView->render('common/resource-select-sidebar');
    }

    public function valueLanguages(): array
    {
        return $this->adminFormService->getSettings()->get('value_languages', []);
    }

    private function setAutocomplete($element): void
    {
        $class = trim((string) $element->getAttribute('class'));
        $class = trim(str_replace('chosen-select', '', $class));
        $element->setAttribute('class', $class);
        $element->setAttribute('data-controller', 'symfony--ux-autocomplete--autocomplete');
    }
}
