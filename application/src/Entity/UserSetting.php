<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\User;
use Omeka\Entity\CASCADE;

#[ORM\Entity]
class UserSetting extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 190)]
    protected $id;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: CASCADE::class)]
    protected $user;

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

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
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
