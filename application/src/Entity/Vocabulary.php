<?php

declare(strict_types=1);

namespace Omeka\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\User;
use Omeka\Entity\ResourceClass;
use Omeka\Entity\Property;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A vocabulary.
 *
 * Vocabularies are defined sets of classes and properties.
 */
#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['vocabulary:read']],
)]
class Vocabulary extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['vocabulary:read', 'property:read', 'resource_template:detail', 'resource_class:read'])]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "vocabularies")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $owner;

    #[ORM\Column(unique: true, length: 190)]
    #[Groups(['vocabulary:read', 'property:read', 'resource_template:detail', 'resource_class:read'])]
    protected $namespaceUri;

    #[ORM\Column(unique: true, length: 190)]
    #[Groups(['vocabulary:read', 'property:read', 'resource_template:detail', 'resource_class:read'])]
    protected $prefix;

    #[ORM\Column]
    #[Groups(['vocabulary:read', 'property:read', 'resource_template:detail', 'resource_class:read'])]
    protected $label;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['vocabulary:read'])]
    protected $comment;

    #[ORM\OneToMany(targetEntity: ResourceClass::class, mappedBy: "vocabulary", orphanRemoval: true, cascade: ["persist", "remove"])]
    #[ORM\OrderBy(["label" => "ASC"])]
    protected $resourceClasses;

    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: "vocabulary", orphanRemoval: true, cascade: ["persist", "remove"])]
    #[ORM\OrderBy(["label" => "ASC"])]
    protected $properties;

    public function __construct()
    {
        $this->resourceClasses = new ArrayCollection();
        $this->properties = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(?User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setNamespaceUri($namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    public function getNamespaceUri()
    {
        return $this->namespaceUri;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function getResourceClasses()
    {
        return $this->resourceClasses;
    }

    public function getProperties()
    {
        return $this->properties;
    }
}
