<?php
namespace Omeka\Log\Processor;

use Omeka\Stdlib\PsrInterpolateTrait;

class PsrPlaceholder
{
    use PsrInterpolateTrait;

    /**
     * @param array|object $record
     * @return array|object
     */
    public function __invoke($record)
    {
        if (is_array($record)) {
            $record['message'] = $this->interpolate($record['message'], $record['context']);
            return $record;
        }

        $message = $this->interpolate($record->message, $record->context);
        if (method_exists($record, 'with')) {
            return $record->with(message: $message);
        }
        $record->message = $message;
        return $record;
    }
}
