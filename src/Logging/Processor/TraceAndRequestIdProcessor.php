<?php

namespace JellyTony\Observability\Logging\Processor;

use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Contracts\LogRecord;
use JellyTony\Observability\Contracts\LogProcessor;

class TraceAndRequestIdProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        // 获取请求 ID 和 Trace ID
        $traceId = !empty($_SERVER[Constant::HTTP_X_B3_TRACE_ID]) ? $_SERVER[Constant::HTTP_X_B3_TRACE_ID] : '';
        $requestId = !empty($_SERVER[Constant::HTTP_X_REQUEST_ID]) ? $_SERVER[Constant::HTTP_X_REQUEST_ID] : '';

        // 将请求 ID 和 Trace ID 添加到日志记录中
        if ($requestId) {
            $record->set('request_id', $requestId);
        }
        if ($traceId) {
            $record->set('trace_id', $traceId);
        }

        return $record;
    }
}