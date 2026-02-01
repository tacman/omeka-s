<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Migration extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 16)]
    protected $version;

    public function getId()
    {
        return $this->version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}
