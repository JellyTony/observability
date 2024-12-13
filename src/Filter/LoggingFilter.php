<?php

namespace JellyTony\Observability\Filter;

use Closure;
use Illuminate\Support\Str;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;
use JellyTony\Observability\Util\HeaderFilter;

class LoggingFilter implements Filter
{
    public $prefix = 'observability.middleware.client.logging.';

    /**
     * 感兴趣的请求，包含调试模式，和业务状态大于 1000 的请求
     * @var bool
     */
    private $interested;

    private $headerFilter;

    public function __construct()
    {
        $this->headerFilter = new HeaderFilter([
            'allowed_headers' => $this->config('allowed_headers'), // 允许的头部
            'sensitive_headers' => $this->config('sensitive_headers'), // 敏感的头部
            'sensitive_input' => $this->config('sensitive_input'),  // 敏感的输入
        ]);
    }

    /**
     * @param $key
     * @param $default
     * @return void
     */
    public function config($key = null, $default = null)
    {
        return config($this->prefix . $key, $default);
    }


    /**
     * 判断是否需要过滤
     * @param string $path
     * @return bool
     */
    protected function shouldBeExcluded(string $path): bool
    {
        foreach ($this->config('excluded_paths') as $excludedPath) {
            if (Str::is($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    public function handle(Context $context, Closure $next, array $options = [])
    {
        // filter path exclude.
        $path = $context->getRequest()->getUri()->getPath();
        if ($this->shouldBeExcluded($path) || $this->config('disabled')) {
            return $next($context, $options);
        }

        $startTime = microtime(true);
        if (!empty($options['start_time'])) {
            $startTime = $options['start_time'];
        }

        try {
            $reply = $next($context, $options);
            $endTime = microtime(true);
            $this->terminate($context, $startTime, $endTime, $options);
            return $reply;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            list($bizCode, $bizMsg) = convertExceptionToBizError($e);
            $context->setBizResult($bizCode, $bizMsg);
            $this->terminate($context, $startTime, $endTime, $options);
            throw $e;
        }
    }

    public function terminate(Context $context, $startTime, $endTime, $options)
    {
        if (!empty($options['end_time'])) {
            $endTime = $options['end_time'];
        }
        $service = 'unknown';
        if (!empty($options['service'])) {
            $service = $options['service'];
        }

        // 计算持续时间（毫秒）
        $duration = ($endTime - $startTime) * 1000;
        $latency = round($duration, 2);

        // 慢请求、或者特定标识的请求
        if ($context->isError() || $latency > $this->config('latency_threshold') || isMpDebug()) {
            $this->interested = true;
        }

        $fields = [
            'start' => date($this->config('time_format'), $startTime),
            "kind" => "client",
            "component" => "http",
            "route" => $context->getRequest()->getUri()->getPath(),
            'latency' => $latency,
            "target_service" => getServiceName($service),
            "biz_code" => $context->getBizCode(),
            "biz_msg" => $context - getBizMsg()
        ];

        // 打印请求头
        if ($this->interested || $this->config('request_headers')) {
            $fields['req_header'] = $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($context->getRequest()->getHeaders()));
        }
        // 打印响应头
        if ($this->interested || $this->config('response_headers')) {
            $fields['reply_header'] = $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($context->getResponse()->getHeaders()));
        }

        // 打印请求数据
        if (($this->interested || $this->config('request_body'))) {
            $maxSize = $this->config('request_body_max_size', 0);
            if ($maxSize > 0 && $context->getRequest()->getBodySize() <= $maxSize) {
                $fields['req_size'] = $context->getRequest()->getBodySize();
                $fields['req_body'] = json_encode($this->headerFilter->filterInput($context->getRequest()->getData()));
            }
        }

        // 打印响应数据
        if ($this->interested || $this->config('response_body')) {
            $maxSize = $this->config('response_body_max_size', 0);
            if ($maxSize > 0 && $context->getResponse()->getBodySize() <= $maxSize) {
                $fields['reply_size'] = $context->getResponse()->getBodySize();
                $fields['reply_body'] = $context->getResponse()->getBody();
            }
        }

        // 根据慢日志阈值和错误情况记录日志
        if ($latency > $this->config('latency_threshold')) {
            if (isset($fields['error'])) {
                \Log::error('http client slow', ['global_fields' => $fields]);
            } else {
                \Log::info('http client slow', ['global_fields' => $fields]);
            }
        } elseif (isset($fields['error'])) {
            \Log::error('http client', ['global_fields' => $fields]);
        } else {
            if ($this->interested || $this->config('access_level') == 'info') {
                \Log::info('http client', ['global_fields' => $fields]);
            } else {
                \Log::debug('http client', ['global_fields' => $fields]);
            }
        }
    }
}