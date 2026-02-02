<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\Persistence\Proxy;
use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract entity.
 */
abstract class AbstractEntity implements EntityInterface
{
    public function getResourceId()
    {
        // Get the real name of this entity, even if it is a Doctrine proxy.
        if ($this instanceof Proxy) {
            return get_parent_class($this) ?: get_class($this);
        }
        return get_class($this);
    }
}
