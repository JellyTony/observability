<?php

namespace JellyTony\Observability\Logging;

use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Logging\Processor\ErrorStackProcessor;
use JellyTony\Observability\Logging\Processor\FieldCleanupProcessor;
use JellyTony\Observability\Logging\Processor\GlobalFieldsProcessor;
use JellyTony\Observability\Logging\Processor\LogLevelProcessor;
use JellyTony\Observability\Logging\Processor\ServiceInfoProcessor;
use JellyTony\Observability\Logging\Processor\TimestampProcessor;
use JellyTony\Observability\Logging\Processor\TraceAndRequestIdProcessor;

class LogPipeline
{
    private $processors;

    public function __construct(array $processors = [])
    {
        if (empty($processors)) {
            $processors = [
                new TraceAndRequestIdProcessor(),
                new ErrorStackProcessor(),
                new TimestampProcessor(),
                new GlobalFieldsProcessor(),
                new LogLevelProcessor(),
                new ServiceInfoProcessor(),
                new FieldCleanupProcessor(),
            ];
        }
        $this->processors = $processors;
    }

    public function process(LogRecord $record): LogRecord
    {
        // 按顺序处理每个处理器
        foreach ($this->processors as $processor) {
            if ($processor instanceof LogProcessor) {
                $record = $processor->process($record);
            } else {
                throw new \InvalidArgumentException('Invalid processor type');
            }
        }

        return $record;
    }
}