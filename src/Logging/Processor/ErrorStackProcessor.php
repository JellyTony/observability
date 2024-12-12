<?php

namespace JellyTony\Observability\Logging\Processor;

use Monolog\Logger;
use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Contracts\LogRecord;

class ErrorStackProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        if ($record->get('level') == Logger::ERROR) {
            $debug_backtrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 6, 10);
            $file = substr($debug_backtrace[0]['file'], strlen(dirname(__DIR__)));
            $line = $debug_backtrace[0]['line'];
            $record->set('caller', "$file:$line");
            $record->set('stack', json_encode($debug_backtrace));
        }

        return $record;
    }
}