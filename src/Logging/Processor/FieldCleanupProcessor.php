<?php

namespace JellyTony\Observability\Logging\Processor;

use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Contracts\LogRecord;

class FieldCleanupProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        if (!empty($record['message'])) {
            $record['msg'] = $record['message'];
            unset($record['message']);
        }
        if ($record->has('message')) {
            $record->set('msg', $record->get('message'));
            $record->delete('message');
        }
        if (empty($record->get('extra'))) {
            $record->delete('extra');
        }
        if (empty($record->get('context'))) {
            $record->delete('context');
        }
        $record->delete('channel');
        return $record;
    }
}