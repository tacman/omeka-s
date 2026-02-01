<?php

declare(strict_types=1);

namespace Omeka\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Omeka\Entity\User;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Job extends AbstractEntity
{
    public const STATUS_STARTING = 'starting';
    public const STATUS_STOPPING = 'stopping';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_ERROR = 'error';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(nullable: true)]
    protected $pid;

    #[ORM\Column(nullable: true)]
    protected $status;

    #[ORM\Column]
    protected $class;

    #[ORM\Column(type: "json_array", nullable: true)]
    protected $args;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $log;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    protected $owner;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $started;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $ended;

    public function getId()
    {
        return $this->id;
    }

    public function setPid($pid)
    {
        $this->pid = is_null($pid) ? null : trim($pid);
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setClass($class)
    {
        $this->class = trim($class);
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setLog($log)
    {
        $this->log = $log;
    }

    public function addLog($log)
    {
        $this->log .= $log . PHP_EOL;
    }

    public function getLog()
    {
        return $this->log;
    }

    public function setOwner(?User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setStarted(DateTime $started)
    {
        $this->started = $started;
    }

    public function getStarted()
    {
        return $this->started;
    }

    public function setEnded(DateTime $ended)
    {
        $this->ended = $ended;
    }

    public function getEnded()
    {
        return $this->ended;
    }

    #[ORM\PrePersist]
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->started = new DateTime('now');
    }
}
