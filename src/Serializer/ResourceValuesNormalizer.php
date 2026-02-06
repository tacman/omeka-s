<?php

declare(strict_types=1);

namespace App\Serializer;

use Omeka\Entity\Resource;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ResourceValuesNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'RESOURCE_VALUES_NORMALIZER_ALREADY_CALLED';

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        /** @var Resource $object */
        $grouped = [];
        foreach ($object->getValues() as $value) {
            $property = $value->getProperty();
            $vocab = $property->getVocabulary();
            $term = $vocab->getPrefix() . ':' . $property->getLocalName();

            $entry = [
                'type' => $value->getType(),
                'property_id' => $property->getId(),
                'property_label' => $property->getLabel(),
                'is_public' => $value->isPublic(),
            ];

            $type = $value->getType();
            if ($type === 'literal' || str_starts_with($type, 'customvocab:')) {
                $entry['@value'] = $value->getValue();
                if ($value->getLang()) {
                    $entry['@language'] = $value->getLang();
                }
            } elseif ($type === 'uri') {
                $entry['@id'] = $value->getUri();
                if ($value->getValue()) {
                    $entry['o:label'] = $value->getValue();
                }
            } elseif ($type === 'resource' || $type === 'resource:item' || $type === 'resource:media' || $type === 'resource:itemset') {
                $vr = $value->getValueResource();
                if ($vr) {
                    $entry['value_resource_id'] = $vr->getId();
                    $entry['display_title'] = $vr->getTitle();
                }
            } else {
                // Fallback for other types
                if ($value->getValue() !== null) {
                    $entry['@value'] = $value->getValue();
                }
                if ($value->getUri() !== null) {
                    $entry['@id'] = $value->getUri();
                }
            }

            $grouped[$term][] = $entry;
        }

        return array_merge($data, $grouped);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!empty($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Resource;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Resource::class => false,
        ];
    }
}
