<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\Resource;
use Omeka\Entity\Property;
use Omeka\Entity\ValueAnnotation;

/**
 * A value, representing the object in a RDF triple.
 */
#[ORM\Entity]
#[ORM\Table(
    name: "`value`",
    indexes: [
new ORM\Index(name: "`value`", columns: ["`value`"], options: ["lengths" => [190]]),
new ORM\Index(name: "`uri`", columns: ["`uri`"], options: ["lengths" => [190]]),
new ORM\Index(name: "is_public", columns: ["is_public"])
],
)]
class Value extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Resource::class, inversedBy: "values")]
    #[ORM\JoinColumn(nullable: false)]
    protected $resource;

    #[ORM\ManyToOne(targetEntity: Property::class, inversedBy: "values")]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected $property;

    #[ORM\Column]
    protected $type;

    #[ORM\Column(nullable: true)]
    protected $lang;

    #[ORM\Column(name: "`value`", type: Types::TEXT, nullable: true)]
    protected $value;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $uri;

    #[ORM\ManyToOne(targetEntity: Resource::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $valueResource;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected $isPublic = true;

    #[ORM\OneToOne(targetEntity: ValueAnnotation::class, orphanRemoval: true, cascade: ["persist"])]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $valueAnnotation;

    public function getId()
    {
        return $this->id;
    }

    public function setResource(?Resource $resource = null)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setProperty(Property $property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setValueResource(?Resource $valueResource = null)
    {
        $this->valueResource = $valueResource;
    }

    public function getValueResource()
    {
        return $this->valueResource;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function getIsPublic()
    {
        return (bool) $this->isPublic;
    }

    public function isPublic()
    {
        return $this->getIsPublic();
    }

    public function setValueAnnotation(?ValueAnnotation $valueAnnotation = null)
    {
        $this->valueAnnotation = $valueAnnotation;
    }

    public function getValueAnnotation()
    {
        return $this->valueAnnotation;
    }
}
