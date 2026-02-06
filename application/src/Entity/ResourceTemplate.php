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
use Omeka\Entity\ResourceTemplateProperty;
use Omeka\Entity\Resource;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['resource_template:read']]),
        new Get(normalizationContext: ['groups' => ['resource_template:read', 'resource_template:detail']]),
    ],
)]
class ResourceTemplate extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['resource_template:read', 'resource:read'])]
    protected $id;

    #[ORM\Column(unique: true, length: 190)]
    #[Groups(['resource_template:read', 'resource:read'])]
    protected $label;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "resourceTemplates")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Groups(['resource_template:read'])]
    protected $owner;

    #[ORM\ManyToOne(targetEntity: ResourceClass::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Groups(['resource_template:read'])]
    protected $resourceClass;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $titleProperty;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $descriptionProperty;

    #[ORM\OneToMany(
        targetEntity: ResourceTemplateProperty::class,
        mappedBy: "resourceTemplate",
        orphanRemoval: true,
        cascade: ["persist", "remove", "detach"],
        indexBy: "property_id",
    )]
    #[ORM\OrderBy(["position" => "ASC"])]
    #[Groups(['resource_template:detail'])]
    protected $resourceTemplateProperties;

    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: "resourceTemplate", fetch: 'EXTRA_LAZY')]
    protected $resources;

    public function __construct()
    {
        $this->resourceTemplateProperties = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setOwner(?User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setResourceClass(?ResourceClass $resourceClass = null)
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setTitleProperty(?Property $titleProperty = null)
    {
        $this->titleProperty = $titleProperty;
    }

    public function getTitleProperty()
    {
        return $this->titleProperty;
    }

    public function setDescriptionProperty(?Property $descriptionProperty = null)
    {
        $this->descriptionProperty = $descriptionProperty;
    }

    public function getDescriptionProperty()
    {
        return $this->descriptionProperty;
    }

    public function getResourceTemplateProperties()
    {
        return $this->resourceTemplateProperties;
    }
}
