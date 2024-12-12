<?php

namespace JellyTony\Observability\Logging\Processor;

use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Contracts\LogRecord;

class GlobalFieldsProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        if (!$record->has('context')) {
            return $record;
        }

        $context = $record->get('context');
        // 如果 'global_fields' 存在并且不为空，将其内容提取到主记录中
        if (!empty($context['global_fields'])) {
            foreach ($context['global_fields'] as $key => $value) {
                $record->set($key, $value);
            }

            // 删除 'global_fields' 字段
            unset($context['global_fields']);
            $record->set('context', $context);
        }


        return $record;
    }
}