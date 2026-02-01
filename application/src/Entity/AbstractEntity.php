<?php

declare(strict_types=1);

namespace Omeka\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract entity.
 */
abstract class AbstractEntity implements EntityInterface
{
    public function getResourceId()
    {
        // Get the real name of this entity, even if it is a Doctrine proxy.
        return ClassUtils::getClass($this);
    }
}
