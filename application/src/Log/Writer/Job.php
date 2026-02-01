<?php
namespace Omeka\Log\Writer;

use Omeka\Entity\Job as JobEntity;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class Job extends AbstractProcessingHandler
{
    /**
     * @var JobEntity
     */
    protected $job;

    /**
     * @param JobEntity $job
     */
    public function __construct(JobEntity $job, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->job = $job;
        $this->setFormatter(new LineFormatter('%datetime% %level_name% (%level%): %message%' . "\n"));
    }

    /**
     * Log to the Job entity.
     *
     * @param array $event
     */
    /**
     * @param array|object $record
     */
    protected function write($record): void
    {
        if (is_array($record)) {
            $message = $record['formatted'] ?? $record['message'];
        } else {
            $message = $record->formatted ?? $record->message;
        }
        $this->job->addLog($message);
    }
}
