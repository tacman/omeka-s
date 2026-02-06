<?php

declare(strict_types=1);

namespace Omeka\Entity;

use ApiPlatform\Metadata\ApiProperty;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\User;
use Omeka\Entity\ResourceClass;
use Omeka\Entity\ResourceTemplate;
use Omeka\Entity\Asset;
use Omeka\Entity\Value;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A resource, representing the subject in an RDF triple.
 *
 * Note that the discriminator map is loaded dynamically.
 *
 *
 * @see \Omeka\Db\Event\Listener\ResourceDiscriminatorMap
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: "resource_type", type: Types::STRING)]
#[ORM\Table(indexes: [
new ORM\Index(
name: "title",
columns: ["title"],
options: ["lengths" => [190]]
),
new ORM\Index(
name: "is_public",
columns: ["is_public"]
)
])]
abstract class Resource extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[ApiProperty(identifier: true)]
    #[Groups(['resource:read'])]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Groups(['resource:read'])]
    protected $owner;

    #[ORM\ManyToOne(targetEntity: ResourceClass::class, inversedBy: "resources")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Groups(['resource:read'])]
    protected $resourceClass;

    #[ORM\ManyToOne(targetEntity: ResourceTemplate::class, inversedBy: "resources")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Groups(['resource:read'])]
    protected $resourceTemplate;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $thumbnail;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['resource:read'])]
    protected $title;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['resource:read'])]
    protected $isPublic = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['resource:read'])]
    protected $created;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['resource:read'])]
    protected $modified;

    #[ORM\OneToMany(targetEntity: Value::class, mappedBy: "resource", orphanRemoval: true, cascade: ["persist", "remove", "detach"])]
    #[ORM\OrderBy(["id" => "ASC"])]
    protected $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    /**
     * Get the resource name of the corresponding entity API adapter.
     *
     * This can be used when the entity is known but the corresponding adapter
     * is not. Primarily used when extracting children of this class (Item,
     * Media, ItemSet, etc.) to an array when the adapter is unknown.
     *
     * @return string
     */
    abstract public function getResourceName();

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

    public function setResourceClass(?ResourceClass $resourceClass = null)
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setResourceTemplate(?ResourceTemplate $resourceTemplate = null)
    {
        $this->resourceTemplate = $resourceTemplate;
    }

    public function getResourceTemplate()
    {
        return $this->resourceTemplate;
    }

    public function setThumbnail(?Asset $thumbnail = null)
    {
        $this->thumbnail = $thumbnail;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setTitle($title)
    {
        // Unlike a resource value, a resource title cannot be an empty string
        // or a string containing only whitespace.
        $title = trim((string) $title);
        $this->title = '' === $title ? null : $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setModified(DateTime $dateTime)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function getValues()
    {
        return $this->values;
    }
}
