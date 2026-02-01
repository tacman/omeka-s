<?php

declare(strict_types=1);

namespace Omeka\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\Asset;
use Omeka\Entity\SitePage;
use Omeka\Entity\User;
use Omeka\Entity\SitePermission;
use Omeka\Entity\SiteItemSet;
use Omeka\Entity\Item;

#[ORM\Entity]
class Site extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(length: 190, unique: true)]
    protected $slug;

    #[ORM\Column(length: 190)]
    protected $theme;

    #[ORM\Column(length: 190)]
    protected $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $summary;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $thumbnail;

    #[ORM\Column(type: "json_array")]
    protected $navigation;

    #[ORM\OneToOne(targetEntity: SitePage::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $homepage;

    #[ORM\Column(type: "json_array")]
    protected $itemPool;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "sites")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $owner;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $created;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $modified;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected $isPublic = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected $assignNewItems = false;

    #[ORM\OneToMany(targetEntity: SitePage::class, mappedBy: "site", orphanRemoval: true, cascade: ["persist", "remove"], indexBy: "id")]
    protected $pages;

    #[ORM\OneToMany(targetEntity: SitePermission::class, mappedBy: "site", orphanRemoval: true, cascade: ["persist", "remove"])]
    protected $sitePermissions;

    #[ORM\OneToMany(targetEntity: SiteItemSet::class, mappedBy: "site", orphanRemoval: true, cascade: ["persist", "remove"])]
    #[ORM\OrderBy(["position" => "ASC"])]
    protected $siteItemSets;

    #[ORM\ManyToMany(targetEntity: Item::class, mappedBy: "sites", fetch: 'EXTRA_LAZY')]
    protected $items;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->sitePermissions = new ArrayCollection();
        $this->siteItemSets = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function setThumbnail(?Asset $thumbnail = null)
    {
        $this->thumbnail = $thumbnail;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    public function getNavigation()
    {
        return $this->navigation;
    }

    public function setHomepage(?SitePage $homepage = null)
    {
        $this->homepage = $homepage;
    }

    public function getHomepage()
    {
        return $this->homepage;
    }

    public function setItemPool($itemPool)
    {
        $this->itemPool = $itemPool;
    }

    public function getItemPool()
    {
        return $this->itemPool;
    }

    public function setOwner(?User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
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

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setAssignNewItems($assignNewItems)
    {
        $this->assignNewItems = (bool) $assignNewItems;
    }

    public function getAssignNewItems()
    {
        return $this->assignNewItems;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getSitePermissions()
    {
        return $this->sitePermissions;
    }

    public function getSiteItemSets()
    {
        return $this->siteItemSets;
    }

    public function getItems()
    {
        return $this->items;
    }
}
