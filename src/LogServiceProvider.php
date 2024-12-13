<?php

namespace JellyTony\Observability;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Illuminate\Support\ServiceProvider;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Logging\LogPipeline;
use JellyTony\Observability\Logging\LogRecord;

class LogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $logLevel = strtoupper(env("LOG_LEVEL", 'info'));
        $allLevels = Logger::getLevels();
        $level = Logger::ERROR;
        if (isset($allLevels[$logLevel])) {
            $level = $allLevels[$logLevel];
        }
        if (isset($_SERVER[Constant::HTTP_MP_DEBUG])) {
            $level = Logger::DEBUG;
        }

        $handler = new StreamHandler(config('observability.log_path'), $level);
        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES));
        // 创建日志流水线
        $pipeline = new LogPipeline();
        $handler->pushProcessor(function ($record) use ($pipeline) {
            $logRecord = new LogRecord($record); // 将原始日志记录转换为 LogRecord 对象
            // 使用流水线处理日志
            $processedRecord = $pipeline->process($logRecord);
            $newRecord = $processedRecord->all();
            if (!empty($newRecord)) {
                return $newRecord;
            }
            return $record;
        });

        $this->app['log']->setHandlers([$handler]);
    }
}