<?php

namespace Omeka\Db\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

class JsonArray extends JsonType
{
    public const JSON_ARRAY = 'json_array';

    public function getName(): string
    {
        return self::JSON_ARRAY;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
