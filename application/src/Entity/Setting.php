<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Setting extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 190)]
    protected $id;

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

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
