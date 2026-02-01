<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Module extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 190)]
    protected $id;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected $isActive = false;

    #[ORM\Column]
    protected $version;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
