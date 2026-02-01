<?php
namespace Omeka\Db\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Custom mapping type for an IP address
 */
class IpAddress extends Type
{
    const IP_ADDRESS = 'ip_address';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARBINARY(16)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return is_null($value) ? null : inet_ntop($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return is_null($value) ? null : inet_pton($value);
    }

    public function getName()
    {
        return self::IP_ADDRESS;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
