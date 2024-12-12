<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Metadata\Metadata;

class RequestLogging
{
    /**
     * @var Repository
     */
    private $config;

    private $debug;

    private $prefix;


    /**
     * TraceRequests constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->prefix = 'observability.middleware.request.';
        $this->config = $config;
        $this->debug = $config->get('observability.middleware.debug');
    }

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // 执行请求并捕获响应
        $response = $next($request);

        // 结束时间
        $endTime = microtime(true);

        // 计算持续时间（毫秒）
        $duration = ($endTime - $startTime) * 1000;
        $latency = round($duration, 2);

        $fields = [
            'start' => date($this->config->get($this->prefix . 'time_format'), (int)$startTime),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'real_ip' => $request->ip(),
            'kind' => 'server',
            'component' => 'http',
            'route' => $request->path(),
            'caller_service' => $request->header('x-md-local-caller_service', 'unknown'),
            'latency' => $latency,
            'http_status' => $response->status(),
            'http_size' => strlen($response->getContent()),
            'biz_code' => bizCode(),
            'biz_msg' => bizMsg()
        ];

        if ($latency > $this->config->get($this->prefix . 'latency_threshold') || isMpDebug()) {
            $this->debug = true;
        }

        // 判断是否需要打印请求参数和返回数据
        if ($this->debug || $this->config->get($this->prefix . 'dump_request_body')) {
            $fields['req_body'] = $request->all();
        }
        if ($this->debug || $this->config->get($this->prefix . 'dump_request_headers')) {
            $fields['req_header'] = $this->formatHeaders($request->headers->all());
        }
        if (($this->debug || $this->config->get($this->prefix . 'dump_response_headers')) && !empty($response->headers->all())) {
            $fields['reply_header'] = $this->formatHeaders($response->headers->all());
        }

        if (!empty($reply) && $reply instanceof JsonResponse) {
            // 获取 JSON 响应的数据
            $responseData = $reply->getData(true);  // 将数据获取为数组

            if (($this->debug || $this->config->get($this->prefix . 'dump_response_body')) && !empty($responseData)) {
                $fields['reply_body'] = $responseData;
            }
        }


        // 根据慢日志阈值和错误情况记录日志
        if ($this->config->get($this->prefix . 'latency_threshold') > 0 && $latency > $this->config->get($this->prefix . 'latency_threshold')) {
            if (isset($fields['error'])) {
                \Log::error('http server slow', ['global_fields' => $fields]);
            } else {
                \Log::info('http server slow', ['global_fields' => $fields]);
            }
        } elseif (isset($fields['error'])) {
            \Log::error('http server', ['global_fields' => $fields]);
        } else {
            \Log::debug('http server', ['global_fields' => $fields]);
        }

        return $response;
    }


    /**
     * 格式化头信息
     *
     * @param array $headers
     * @return array
     */
    protected function formatHeaders(array $headers): array
    {
        return array_map(function ($value) {
            return implode(', ', $value);
        }, $headers);
    }
}
