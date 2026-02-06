<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\Form\FormInterface;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Form\ResourceForm;

final class OmekaAdminFormService
{
    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
        private readonly LegacyViewRenderer $legacyViewRenderer,
    ) {
    }

    public function createResourceForm(
        AbstractResourceEntityRepresentation $resource,
        string $actionUrl,
        string $formId,
        bool $disableCsrf = true,
    ): FormInterface {
        $formElementManager = $this->legacyApp->getServiceManager()->get('FormElementManager');
        $form = $formElementManager->get(ResourceForm::class, ['resource' => $resource]);
        $form->setAttribute('action', $actionUrl);
        $form->setAttribute('enctype', 'multipart/form-data');
        $form->setAttribute('id', $formId);

        // Remove CSRF for dev - the Omeka CSRF initializer adds it automatically
        // TODO: Properly handle CSRF tokens between Symfony and Laminas
        if ($disableCsrf) {
            // Try both possible CSRF element names
            $csrfNames = ['csrf'];
            if ($form->getName()) {
                $csrfNames[] = sprintf('%s_csrf', $form->getName());
            }
            foreach ($csrfNames as $csrfName) {
                if ($form->has($csrfName)) {
                    $form->remove($csrfName);
                    // Also remove from input filter if it exists
                    $inputFilter = $form->getInputFilter();
                    if ($inputFilter->has($csrfName)) {
                        $inputFilter->remove($csrfName);
                    }
                }
            }
        }

        return $form;
    }

    public function getMediaForms(): array
    {
        $services = $this->legacyApp->getServiceManager();
        $mediaIngesters = $services->get('Omeka\Media\Ingester\Manager');
        $viewHelperManager = $services->get('ViewHelperManager');
        $mediaHelper = $viewHelperManager->get('media');
        $mediaHelper->setView($this->legacyViewRenderer->getRenderer());

        $forms = [];
        foreach ($mediaIngesters->getRegisteredNames() as $ingester) {
            $forms[$ingester] = [
                'label' => $mediaIngesters->get($ingester)->getLabel(),
                'form' => $mediaHelper->form($ingester),
            ];
        }

        return $forms;
    }

    public function getSettings()
    {
        return $this->legacyApp->getServiceManager()->get('Omeka\Settings');
    }

    public function mergeValuesJson(array $data): array
    {
        if (!isset($data['values_json']) || !$data['values_json']) {
            return $data;
        }

        $jsonData = json_decode($data['values_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON error: ' . json_last_error_msg());
        }
        unset($data['values_json']);

        return array_merge($data, $jsonData);
    }

    /**
     * Validate form data, optionally skipping CSRF validation.
     */
    public function validateForm(FormInterface $form, array $data, bool $skipCsrf = true): bool
    {
        if ($skipCsrf) {
            // Remove CSRF from data and input filter before validation
            unset($data['csrf']);
            $csrfName = $form->getName() ? sprintf('%s_csrf', $form->getName()) : 'csrf';
            unset($data[$csrfName]);

            // Get input filter and remove CSRF input
            $inputFilter = $form->getInputFilter();
            if ($inputFilter->has('csrf')) {
                $inputFilter->remove('csrf');
            }
            if ($inputFilter->has($csrfName)) {
                $inputFilter->remove($csrfName);
            }
        }

        $form->setData($data);
        return $form->isValid();
    }
}
