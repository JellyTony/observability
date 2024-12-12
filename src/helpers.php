<?php

use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Metadata\Metadata;
use JellyTony\Observability\Filter\FilterPipeline;

if (!function_exists('getServiceName')) {
    function getServiceName(string $service): string
    {
        if (empty($service)) {
            return 'unknown';
        }
        $service = strtolower($service);
        if (strpos($service, "micro-service-") === 0) {
            return substr($service, 15);
        }
        if (strpos($service, "ms.") === 0) {
            return substr($service, 3);
        }
        if (strpos($service, "ms_") === 0) {
            return substr($service, 3);
        }
        // micro_service_article_slb
        if (strpos($service, "micro_service_") === 0 && strpos($service, "_slb") === (strlen($service) - 4)) {
            $service = str_replace("micro_service_", "", $service);
            $service = str_replace("_slb", "", $service);
        }

        return $service;
    }
}

if (!function_exists('appEnv')) {
    function appEnv(): string
    {
        return env('APP_DEPLOY_ENV', 'test');
    }
}

if (!function_exists('appId')) {
    function appId(): int
    {
        return env('APP_ID', 1000);
    }
}

if (!function_exists('appName')) {
    function appName(): string
    {
        return getServiceName(env('APP_NAME', 'microservice'));
    }
}

if (!function_exists('appVersion')) {
    function appVersion(): string
    {
        return env('APP_VERSION', '1.0.0');
    }
}

if (!function_exists('mpDebug')) {
    function mpDebug(): bool
    {
        if (isset($_SERVER[Constant::HTTP_MP_DEBUG]) || (bizCode()) > 1000) {
            return true;
        }

        return false;
    }
}

if (!function_exists('bizCode')) {
    function bizCode(): int
    {
        $bizCode = Metadata::get(Constant::BIZ_CODE);
        return $bizCode ? $bizCode : 1000;
    }
}

if (!function_exists('bizMsg')) {
    function bizMsg(): string
    {
        $bizMsg = Metadata::get(Constant::BIZ_MSG);
        return $bizMsg ? $bizMsg : 'ok';
    }
}

if (!function_exists('createFilterRequest')) {
    function createFilterRequest(string $method, $uri, array $headers = [], $body = null, string $version = '1.1'): Request
    {
        return new Request($method, $uri, $headers, $body, $version);
    }
}

if (!function_exists('filter')) {
    function filter($request, \Closure $finalHandler, array $middlewares = [], array $options = [])
    {
        return FilterPipeline::run($request, $finalHandler, $middlewares, $options);
    }
}

// 保存异常错误信息
if (!function_exists('saveError')) {
    function saveError(\Exception $e)
    {
        \Log::error("Caught exception: " . $e->getCode() . "msg: " . $e->getMessage());

        $bizCode = 1004;
        if ($e->getCode() > 1000) {
            $bizCode = $e->getCode();
        }

        Metadata::set(Constant::BIZ_CODE, $bizCode);
        Metadata::set(Constant::BIZ_MSG, $e->getMessage());
    }
}