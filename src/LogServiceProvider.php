<?php

namespace JellyTony\Observability;

use Illuminate\Contracts\Config\Repository;
use JellyTony\Observability\Constant\Constant;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * @var Repository
     */
    protected $config;

    protected $releases = '';

    //获取git的tag
    public function getTag()
    {
        $gitTagCommand = "git describe --tags `git rev-list --tags --max-count=1`";
        return rtrim(shell_exec($gitTagCommand));
    }

    public function boot()
    {
        $this->releases = $this->getTag();
        $logLevel = strtoupper(env("LOG_LEVEL", 'error'));
        $allLevels = Logger::getLevels();
        $level = Logger::ERROR;
        if (isset($allLevels[$logLevel])) {
            $level = $allLevels[$logLevel];
        }
        if (isset($_SERVER[Constant::HTTP_MP_DEBUG])) {
            $level = Logger::DEBUG;
        }

        $handler = new StreamHandler(env('LOG_PATH', storage_path("logs/lumen.log")), $level);
        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES));
        $handler->pushProcessor(function ($record) {
            $requestId = "";
            if (isset($_SERVER[Constant::HTTP_X_REQUEST_ID]) && !empty($_SERVER[Constant::HTTP_X_REQUEST_ID])) {
                $requestId = $_SERVER[Constant::HTTP_X_REQUEST_ID];
            }
            $traceId = "";
            if (isset($_SERVER[Constant::HTTP_X_B3_TRACE_ID]) && !empty($_SERVER[Constant::HTTP_X_B3_TRACE_ID])) {
                $traceId = $_SERVER[Constant::HTTP_X_B3_TRACE_ID];
            }

            if ($record['level'] == Logger::ERROR) {
                $debug_backtrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 6, 10);
                $file = substr($debug_backtrace[0]['file'], strlen(dirname(__DIR__)));
                $line = $debug_backtrace[0]['line'];
                $record['caller'] = "$file:$line";
                $record['stack'] = json_encode($debug_backtrace);
            }
            if (!empty($traceId)) {
                $record['trace_id'] = $traceId;
            }
            if (!empty($requestId)) {
                $record['request_id'] = $requestId;
            }
            if (!empty($record['datetime'])) {
                $record['ts'] = $record['datetime']->format('Y-m-d\TH:i:s.vO');
                unset($record['datetime']);
            }

            // 兼容全局字段
            if (isset($record['context']['global_fields'])) {
                foreach ($record['context']['global_fields'] as $key => $value) {
                    $record[$key] = $value;
                }
                unset($record['context']['global_fields']);
            }
            if (empty($record['context'])) {
                unset($record['context']);
            }
            if (!empty($record['level']) && !empty($record['level_name'])) {
                $record['level'] = strtolower($record["level_name"]);
                unset($record['level_name']);
            }
            if (!empty($record['channel'])) {
                unset($record['channel']);
            }
            if (!empty($record['message'])) {
                $record['msg'] = $record['message'];
                unset($record['message']);
            }
            if (empty($record['extra'])) {
                unset($record['extra']);
            }

            $record['app_id'] = env('APP_ID', 1000);
            $record['app_name'] = env('APP_NAME', 'microservice');
            $record['app_version'] = env('APP_VERSION', '1.0.0');
            $record['deploy_env'] = env('APP_DEPLOY_ENV', 'test');

            return $record;
        });

        $this->app['log']->setHandlers([$handler]);
    }
}