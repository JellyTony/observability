<?php

use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Metadata\Metadata;
use JellyTony\Observability\Filter\FilterPipeline;

if (!function_exists('getServiceName')) {
    /**
     * 获取服务名称，转换成标准格式
     *
     * @param string $service
     * @return string
     */
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
    /**
     * 获取当前应用部署环境
     * @return string
     */
    function appEnv(): string
    {
        return env('APP_DEPLOY_ENV', 'test');
    }
}

if (!function_exists('appId')) {
    /**
     * 获取当前应用的 ID
     * @return int
     */
    function appId(): int
    {
        return env('APP_ID', 1000);
    }
}

if (!function_exists('appName')) {
    /**
     * 获取当前应用的名称
     * @return string
     */
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

if (!function_exists('isMpDebug')) {
    /**
     * 判断是否开启调试模式
     * @return bool
     */
    function isMpDebug(): bool
    {
        return isset($_SERVER[Constant::HTTP_MP_DEBUG]) || (bizCode()) > Constant::BIZ_CODE_SUCCESS;
    }
}

if (!function_exists('bizCode')) {
    /**
     * 获取业务码，如果未设置则返回默认值
     * @return int
     */
    function bizCode(): int
    {
        return (int)Metadata::get(Constant::BIZ_CODE) ?: 1000;
    }
}

if (!function_exists('bizMsg')) {
    /**
     * 获取业务信息，如果未设置则返回默认值
     * @return string
     */
    function bizMsg(): string
    {
        return Metadata::get(Constant::BIZ_MSG) ?: 'ok';
    }
}

if (!function_exists('getRequestId')) {
    /**
     * 获取请求ID
     * @return string
     */
    function getRequestId(): string
    {
        return $_SERVER[Constant::HTTP_X_REQUEST_ID] ?? '';
    }
}

if (!function_exists('getTraceId')) {
    /**
     * 获取traceId
     * @return string
     */
    function getTraceId(): string
    {
        return $_SERVER[Constant::HTTP_X_B3_TRACE_ID] ?? '';
    }
}

if (!function_exists('createFilterRequest')) {
    /**
     * 创建过滤器请求对象
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param mixed $body
     * @param string $version
     * @return Request
     */
    function createFilterRequest(string $method, string $uri, array $headers = [], $body = null, string $version = '1.1'): Request
    {
        return new Request($method, $uri, $headers, $body, $version);
    }
}

if (!function_exists('applyFilter')) {
    /**
     * 应用过滤器链
     *
     * @param mixed $request
     * @param \Closure $finalHandler
     * @param array $middlewares
     * @param array $options
     * @return mixed
     */
    function applyFilter($request, \Closure $finalHandler, array $middlewares = [], array $options = [])
    {
        return FilterPipeline::run($request, $finalHandler, $middlewares, $options);
    }
}

// 异常转换为业务错误
if (!function_exists('convertExceptionToBizError')) {
    /**
     * 将异常转换为业务错误码和消息
     *
     * @param \Exception $exception
     * @return array
     */
    function convertExceptionToBizError(\Exception $exception): array
    {
        $bizCode = $exception->getCode() > Constant::BIZ_CODE_SUCCESS ? $exception->getCode() : 1004;
        return [$bizCode, $exception->getMessage()];
    }
}

// 保存异常错误信息
if (!function_exists('handleServerError')) {
    /**
     * 记录服务器错误并保存到元数据
     *
     * @param \Exception $exception
     */
    function handleServerError(\Exception $exception)
    {
        \Log::error("Server caught exception: Code - {$exception->getCode()}, Message - {$exception->getMessage()}");

        $bizCode = $exception->getCode() > 1000 ? $exception->getCode() : 1004;
        Metadata::set(Constant::BIZ_CODE, $bizCode);
        Metadata::set(Constant::BIZ_MSG, $exception->getMessage());
    }
}

// 获取环境变量数组
if (!function_exists('envArray')) {
    /**
     * 获取环境变量数组
     * @param $key
     * @return array|false|string[]
     */
    function envArray($key, $default = [])
    {
        $val = env($key);
        if (empty($val)) {
            return $default;
        }
        return explode(',', $val);
    }
}