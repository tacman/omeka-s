<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\Site;
use Omeka\Entity\CASCADE;
use Omeka\Entity\User;

#[ORM\Entity]
#[ORM\Table(uniqueConstraints: [
new ORM\UniqueConstraint(
columns: ["site_id", "user_id"]
)
])]
class SitePermission extends AbstractEntity
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: "sitePermissions")]
    #[ORM\JoinColumn(nullable: false, onDelete: CASCADE::class)]
    protected $site;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: CASCADE::class)]
    protected $user;

    #[ORM\Column(length: 80)]
    protected $role;

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

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }
}
