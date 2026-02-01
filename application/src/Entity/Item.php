<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\Media;
use Omeka\Entity\SiteBlockAttachment;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Site;

#[ORM\Entity]
class Item extends Resource
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    protected $primaryMedia;

    #[ORM\OneToMany(
        targetEntity: Media::class,
        mappedBy: "item",
        orphanRemoval: true,
        cascade: ["persist", "remove", "detach"],
        indexBy: "id",
    )]
    #[ORM\OrderBy(["position" => "ASC"])]
    protected $media;

    #[ORM\OneToMany(targetEntity: SiteBlockAttachment::class, mappedBy: "item")]
    protected $siteBlockAttachments;

    #[ORM\ManyToMany(targetEntity: ItemSet::class, inversedBy: "items", indexBy: "id")]
    #[ORM\JoinTable(name: "item_item_set")]
    protected $itemSets;

    #[ORM\ManyToMany(targetEntity: Site::class, inversedBy: "items", indexBy: "id")]
    #[ORM\JoinTable(name: "item_site")]
    protected $sites;

    public function __construct()
    {
        parent::__construct();
        $this->media = new ArrayCollection();
        $this->siteBlockAttachments = new ArrayCollection();
        $this->itemSets = new ArrayCollection();
        $this->sites = new ArrayCollection();
    }

    public function getResourceName()
    {
        return 'items';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPrimaryMedia(?Media $primaryMedia = null)
    {
        $this->primaryMedia = $primaryMedia;
    }

    public function getPrimaryMedia()
    {
        return $this->primaryMedia;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function getSiteBlockAttachments()
    {
        return $this->siteBlockAttachments;
    }

    public function getItemSets()
    {
        return $this->itemSets;
    }

    public function getSites()
    {
        return $this->sites;
    }
}
