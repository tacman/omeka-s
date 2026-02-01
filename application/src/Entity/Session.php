<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Session
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 190)]
    protected $id;

    #[ORM\Column(type: Types::BLOB)]
    protected $data;

    #[ORM\Column(type: Types::INTEGER)]
    protected $modified;
}
