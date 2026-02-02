<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\Site;
use Omeka\Entity\ItemSet;

#[ORM\Entity]
#[ORM\Table(
    uniqueConstraints: [
new ORM\UniqueConstraint(
columns: ["site_id", "item_set_id"]
)
],
    indexes: [
new ORM\Index(
name: "position",
columns: ["position"]
)
],
)]
class SiteItemSet extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: "siteItemSets")]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    private $site;

    #[ORM\ManyToOne(targetEntity: ItemSet::class, inversedBy: "siteItemSets")]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    private $itemSet;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected $position;

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

    public function setItemSet(ItemSet $itemSet)
    {
        $this->itemSet = $itemSet;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }

    public function setPosition($position)
    {
        $this->position = (int) $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
