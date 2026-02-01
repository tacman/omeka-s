<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ValueAnnotation extends Resource
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;

    public function getResourceName()
    {
        return 'value_annotations';
    }

    public function getId()
    {
        return $this->id;
    }
}
