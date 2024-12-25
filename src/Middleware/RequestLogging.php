<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Config\Repository;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Util\HeaderFilter;

class RequestLogging
{
    /**
     * @var Repository
     */
    private $config;

    /**
     * 感兴趣的请求，包含调试模式，和业务状态大于 1000 的请求
     * @var bool
     */
    private $interested;

    private $prefix;

    private $headerFilter;

    /**
     * TraceRequests constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->prefix = 'observability.middleware.server.logging.';
        $this->config = $config;
        $this->interested = false;
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

    public function isRequest($request) :bool
    {
        if(!empty($request)) {
            return true;
        }

        return false;
    }

    public function isResponse($response) :bool
    {
        if (!empty($response) && $response instanceof JsonResponse) {
            return true;
        }

        return false;
    }

    public function handle(Request $request, Closure $next)
    {
        // filter path exclude.
        if ($this->shouldBeExcluded($request->path()) || $this->config('disabled') || !$this->isRequest($request)) {
            return $next($request);
        }

        $startTime = microtime(true);

        // 执行请求并捕获响应
        $response = $next($request);

        // 结束时间
        $endTime = microtime(true);

        // 计算持续时间（毫秒）
        $duration = ($endTime - $startTime) * 1000;
        $latency = round($duration, 2);

        $fields = [
            'start' => date($this->config('time_format'), (int)$startTime),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'real_ip' => $request->ip(),
            'kind' => 'server',
            'component' => 'http',
            'route' => $request->path(),
            'caller_service' => $request->header('x-md-local-caller_service', 'unknown'),
            'latency' => $latency,
            'biz_code' => bizCode(),
            'biz_msg' => bizMsg()
        ];
        if($this->isResponse($response)) {
            $fields['http_status'] = $response->status();
        }
        if(bizCode() > Constant::BIZ_CODE_SUCCESS) {
            $fields['error'] = bizMsg();
        }

        if ($latency > $this->config('latency_threshold') || isMpDebug()) {
            $this->interested = true;
        }

        // 打印请求头
        if ($this->interested || $this->config('request_headers')) {
            $fields['req_header'] = $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($request->headers->all()));
        }
        // 打印响应头
        if (($this->interested || $this->config('response_headers')) && $this->isResponse($response) &&  !empty($response->headers->all())) {
            $fields['reply_header'] = $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($response->headers->all()));
        }

        // 打印请求数据
        if (($this->interested || $this->config('request_body')) && !empty($request->getContent())) {
            $maxSize = $this->config('request_body_max_size', 0);
            $bodySize = strlen($request->getContent());
            if ($maxSize > 0 && $bodySize <= $maxSize) {
                $fields['req_size'] = $bodySize;
                $fields['req_body'] = json_encode($this->headerFilter->filterInput($request->input()));
            }
        }

        // 打印响应数据
        if (($this->interested || $this->config('response_body')) && $this->isResponse($response)) {
            $responseData = $response->getData(true);  // 将数据获取为数组
            $maxSize = $this->config('response_body_max_size', 0);
            $replySize = strlen($response->content());
            if (!empty($responseData) && $maxSize > 0 && $replySize <= $maxSize) {
                $fields['reply_size'] = $replySize;
                $fields['reply_body'] = $responseData;
            }
        }

        // 根据慢日志阈值和错误情况记录日志
        if ($latency > $this->config('latency_threshold')) {
            if (isset($fields['error'])) {
                \Log::error('http server slow', ['global_fields' => $fields]);
            } else {
                \Log::info('http server slow', ['global_fields' => $fields]);
            }
        } elseif (isset($fields['error'])) {
            \Log::error('http server', ['global_fields' => $fields]);
        } else {
            if ($this->interested || $this->config('access_level') == 'info') {
                \Log::info('http server', ['global_fields' => $fields]);
            } else {
                \Log::debug('http server', ['global_fields' => $fields]);
            }
        }

        return $response;
    }
}
