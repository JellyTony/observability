<?php

namespace JellyTony\Observability\Logging\Processor;

use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Contracts\LogRecord;

class LogLevelProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        if ($record->has('level') && $record->has('level_name')) {
            $record->set('level', strtolower($record["level_name"]));
            $record->delete('level_name');
        }
        return $record;
    }
}