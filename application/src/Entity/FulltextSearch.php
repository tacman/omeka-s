<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\User;

#[ORM\Entity]
#[ORM\Table(indexes: [
new ORM\Index(columns: ["title", "text"], flags: ["fulltext"])
])]
class FulltextSearch
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 190)]
    protected $resource;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $owner;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected $isPublic = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $text;

    /**
     * @param int $id
     * @param string $resource
     */
    public function __construct($id, $resource)
    {
        $this->id = $id;
        $this->resource = $resource;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setOwner(?User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function getIsPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }
}
