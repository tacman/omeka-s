<?php
namespace Omeka\Db\Event\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

class Schema implements EventSubscriber
{
    protected string $platformName;

    public function __construct(Connection $connection)
    {
        $platform = $connection->getDatabasePlatform();
        if (method_exists($platform, 'getName')) {
            $this->platformName = $platform->getName();
            return;
        }
        $this->platformName = $platform instanceof SQLitePlatform ? 'sqlite' : '';
    }

    public function getSubscribedEvents(): array
    {
        return [ToolEvents::postGenerateSchemaTable];
    }

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args): void
    {
        if ($this->platformName !== 'sqlite' && $this->platformName !== 'sqlite3') {
            return;
        }

        $table = $args->getClassTable();
        foreach ($table->getColumns() as $column) {
            if ($column->getCollation() !== null) {
                $column->setPlatformOption('collation', null);
            }
            if ($column->getCharset() !== null) {
                $column->setPlatformOption('charset', null);
            }
        }
    }
}
