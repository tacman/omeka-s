<?php

declare(strict_types=1);

namespace App\Menu\Event;

use Knp\Menu\ItemInterface;
use Omeka\Entity\Site;
use Symfony\Contracts\EventDispatcher\Event;

final class SiteMenuEvent extends Event
{
    public function __construct(
        private readonly ItemInterface $menu,
        private readonly Site $site,
    ) {
    }

    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }

    public function getSite(): Site
    {
        return $this->site;
    }
}
