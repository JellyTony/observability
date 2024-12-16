<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Util\HeaderFilter;
use Zipkin\Tags;
use Illuminate\Support\Str;
use Zipkin\Propagation\Map;
use JellyTony\Observability\Facades\Trace;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;
use JellyTony\Observability\Constant\Constant;

class TraceFilter implements Filter
{
    public $prefix = 'observability.middleware.client.trace.';

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
        // 没有开启 trace，忽略
        if (empty(Trace::getTracer()) || empty(Trace::getRootSpan())) {
            return $next($context, $options);
        }

        $service = 'unknown';
        if (!empty($options['service'])) {
            $service = $options['service'];
        }

        // inject headers
        $headers = [];
        $method = $context->getRequest()->getMethod();
        $spanName = sprintf("HTTP Client %s: %s", $method, $path);
        $span = Trace::startSpan($spanName, Trace::getRootSpan()->getContext());
        $span->setKind("CLIENT");
        $span->tag(Tags\HTTP_METHOD, $method);
        $span->tag(Tags\HTTP_PATH, $path);
        $span->tag(Tags\HTTP_HOST, $context->getRequest()->getUri()->getHost());
        $span->tag(Tags\HTTP_URL, $context->getRequest()->getUri()->__toString());
        $span->tag('target.service_name', getServiceName($service));

        // 向  header 头注入 traceId 信息
        $h = [];
        $injector = Trace::getPropagation()->getInjector(new Map());
        $injector($span->getContext(), $h);
        foreach ($h as $k => $v) {
            $headers[$k] = $v;
        }
        $context->getRequest()->setHeaders($headers);

        $startTime = microtime(true);

        try {
            $response = $next($context, $options);
            $endTime = microtime(true);
            $this->terminate($context, $startTime, $endTime, $span);
            return $response;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            list($bizCode, $bizMsg) = convertExceptionToBizError($e);
            $context->setBizResult($bizCode, $bizMsg);
            $this->terminate($context, $startTime, $endTime, $span);
            // 继续抛出异常
            throw $e;
        }
    }

    public function terminate(Context $context, $startTime, $endTime, $span)
    {
        if (!empty($options['end_time'])) {
            $endTime = $options['end_time'];
        }

        // 计算持续时间（毫秒）
        $duration = ($endTime - $startTime) * 1000;
        $latency = round($duration, 2);

        // 慢请求、或者特定标识的请求
        if ($context->isError() || $latency > $this->config('latency_threshold') || isMpDebug()) {
            $this->interested = true;
        }

        $span->tag(Constant::BIZ_CODE, $context->getBizCode());
        if (!empty($context->getBizMsg()) && $context->getBizCode() > Constant::BIZ_CODE_SUCCESS) {
            $span->tag('error', $context->getBizMsg());
        }
        $span->tag(Tags\HTTP_STATUS_CODE, $context->getResponse()->getStatusCode());

        // 上报请求头
        if ($this->interested || $this->config('request_headers')) {
            $span->tag('http.request.headers', $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($context->getRequest()->getHeaders())));
        }
        // 上报响应头
        if ($this->interested || $this->config('response_headers')) {
            $span->tag('http.response.headers', $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($context->getResponse()->getHeaders())));
        }
        // 上报请求请求
        if ($this->interested || $this->config('request_body')) {
            $maxSize = $this->config('request_body_max_size', 0);
            $bodySize = strlen($context->getRequest()->getBodySize());
            if ($maxSize > 0 && $bodySize <= $maxSize) {
                $span->tag('http.request.size', $bodySize);
                $span->tag('http.request.body', base64_encode(json_encode($this->headerFilter->filterInput($context->getRequest()->getData()))));
            }
        }
        // 上报响应数据
        if ($this->interested || $this->config('response_body')) {
            $replySize = $context->getResponse()->getBodySize();
            $maxSize = $this->config('response_body_max_size', 0);
            if ($maxSize > 0 && $replySize <= $maxSize) {
                $span->tag('http.response.size', $replySize);
                $span->tag('http.response.body', base64_encode($context->getResponse()->getBody()));
            }
        }

        // 标志结束
        $span->finish();
    }
}