<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Entity\Value;

/**
 * A property, representing the predicate in an RDF triple.
 *
 * Properties define relationships between resources and their values.
 */
#[ORM\Entity]
#[ORM\Table(uniqueConstraints: [
new ORM\UniqueConstraint(
columns: ["vocabulary_id", "local_name"]
)
])]
class Property extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "properties")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $owner;

    #[ORM\ManyToOne(targetEntity: Vocabulary::class, inversedBy: "properties")]
    #[ORM\JoinColumn(nullable: false)]
    protected $vocabulary;

    #[ORM\Column(options: ["collation" => "utf8mb4_bin"], length: 190)]
    protected $localName;

    #[ORM\Column]
    protected $label;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $comment;

    #[ORM\OneToMany(targetEntity: Value::class, mappedBy: "property", fetch: 'EXTRA_LAZY')]
    protected $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
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

    public function setVocabulary(?Vocabulary $vocabulary = null)
    {
        $this->vocabulary = $vocabulary;
    }

    public function getVocabulary()
    {
        return $this->vocabulary;
    }

    public function setLocalName($localName)
    {
        $this->localName = $localName;
    }

    public function getLocalName()
    {
        return $this->localName;
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
}
