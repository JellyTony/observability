<?php

namespace JellyTony\Observability\Logging\Processor;

use JellyTony\Observability\Contracts\LogProcessor;
use JellyTony\Observability\Contracts\LogRecord;

class ServiceInfoProcessor implements LogProcessor
{
    public function process(LogRecord $record): LogRecord
    {
        $record['app_id'] = env('APP_ID', 1000);
        $record['app_name'] = env('APP_NAME', 'microservice');
        $record['app_version'] = env('APP_VERSION', '1.0.0');
        $record['deploy_env'] = env('APP_DEPLOY_ENV', 'test');

        return $record;
    }
}