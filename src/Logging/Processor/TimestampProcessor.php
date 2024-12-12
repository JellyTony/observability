<?php

namespace JellyTony\Observability\Logging\Processor;

use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Contracts\LogRecord;

class TimestampProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        if ($record->has('datetime')) {
            $record->set('ts', $record->get('datetime')->format('Y-m-d\TH:i:s.vO'));
            $record->delete('datetime');
        }

        return $record;
    }
}