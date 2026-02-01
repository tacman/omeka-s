<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\Site;
use Omeka\Entity\CASCADE;

#[ORM\Entity]
class SiteSetting extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 190)]
    protected $id;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: CASCADE::class)]
    protected $site;

    #[ORM\Column(type: "json_array")]
    protected $value;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
