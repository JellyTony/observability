<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Constant\ResourceAttributes;
use JellyTony\Observability\Contracts\Span;
use JellyTony\Observability\Contracts\Tracer;
use JellyTony\Observability\Metadata\Metadata;
use Symfony\Component\HttpFoundation\HeaderBag;
use Zipkin\Propagation\Map;

class Tracing
{
    /**
     * @var Tracer
     */
    protected $tracer;

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

    /**
     * TraceRequests constructor.
     * @param Tracer $tracer
     * @param Repository $config
     */
    public function __construct(Tracer $tracer, Repository $config)
    {
        $this->prefix = 'observability.middleware.server.trace.';
        $this->tracer = $tracer;
        $this->config = $config;
        $this->interested = false;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // filter path exclude.
        if ($this->shouldBeExcluded($request->path()) || $this->config('disabled') || empty($this->tracer)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $carrier = array_map(function ($header) {
            if ($header[0] == "") {
                return null;
            }
            return $header[0];
        }, $request->headers->all());

        $extractor = $this->tracer->getPropagation()->getExtractor(new Map());
        $extractedContext = $extractor($carrier);
        $spanName = sprintf("HTTP Server %s: %s", $request->method(), $request->path());
        $span = $this->tracer->startSpan($spanName, $extractedContext);
        $traceID = $span->getContext()->getTraceId();
        $_SERVER[Constant::HTTP_X_B3_TRACE_ID] = $traceID;
        Metadata::set(Constant::TRACE_ID, $traceID);

        $this->tagRequestData($span, $request);

        $reply = $next($request);
        if (!empty($reply) && !empty($reply->headers)) {
            $reply->headers->set("x-trace-id", $traceID);
        }
        if ($this->isResponse($reply)) {
            // 获取 JSON 响应的数据
            $responseData = $reply->getData(true);  // 将数据获取为数组
            if (!empty($responseData) && is_array($responseData)) {
                // 设置 biz_code 和 biz_msg（如果响应数据中包含这些字段）
                if (!empty($responseData['code'])) {
                    Metadata::set(Constant::BIZ_CODE, $responseData['code']);
                }
                if (!empty($responseData['msg'])) {
                    Metadata::set(Constant::BIZ_MSG, $responseData['msg']);
                }
            } else {
                $span->addTag("response", "response is not json");
            }
        } else {
            $span->addTag("response", "response is not json");
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        $latency = round($duration, 2);

        // 慢请求、或者特定标识的请求
        if ($latency > $this->config('latency_threshold') || isMpDebug()) {
            $this->interested = true;
        }
        if ($span->getContext()->isSampled() || $latency > $this->config('latency_threshold') || isMpDebug()) {
            $span->getContext()->withSampled(true);
        }

        $this->terminate($span, $request, $reply);

        $this->tagProcess($span);

        $this->finishRootSpan();

        return $reply;
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
     * @param Request $request
     * @param Span $span
     */
    protected function tagRequestData(Span $span, Request $request): void
    {
        if (empty($span) || !$this->isRequest($request)) {
            return;
        }
        $span->addTag('type', 'http');
        $span->setKind("SERVER");
        $span->addTag("http.client_ip", $request->ip());
        $span->addTag("http.user_agent", $request->userAgent());
        $span->addTag("caller.service_name", $request->header('x-md-local-caller_service', 'unknown'));
        $span->addTag("http.method", $request->getMethod());
        $span->addTag("http.url", $request->fullUrl());
        $span->addTag('http.route', $request->path());
        $span->addTag(Constant::BIZ_CODE, bizCode());
        if (bizCode() > Constant::BIZ_CODE_SUCCESS) {
            $span->addTag("error", bizMsg());
        }
    }

    public function isRequest($request): bool
    {
        if (!empty($request)) {
            return true;
        }

        return false;
    }

    public function isResponse($response): bool
    {
        if (!empty($response) && $response instanceof JsonResponse) {
            return true;
        }

        return false;
    }

    /**
     * @param Span $span
     * @param Request $request
     * @param $response
     * @return void
     */
    public function terminate(Span $span, Request $request, $response)
    {
        $route = $request->route();
        $this->tagResponseData($span, $request, $response);

        if ($this->isLaravelRoute($route)) {
            $span->setName(sprintf('HTTP Server %s: %s', $request->method(), $request->route()->uri()));
        }

        if ($this->isLumenRoute($route)) {
            $routeUri = $this->getLumenRouteUri($request->path(), $route[2]);
            $span->setName(sprintf('HTTP Server %s: %s', $request->method(), $routeUri));
        }
    }

    /**
     * @param Span $span
     * @param Request $request
     * @param Response|JsonResponse $response
     */
    protected function tagResponseData(Span $span, Request $request, $response): void
    {
        if (!$this->isRequest($request)) {
            return;
        }

        if ($route = $request->route()) {
            if (method_exists($route, 'getActionName')) {
                $span->addTag('laravel_action', $route->getActionName());
            }
        }

        if ($this->isResponse($response) && !empty($response->getStatusCode())) {
            $span->addTag('http.status_code', strval($response->getStatusCode()));
        }

        // 上报请求头
        if (($this->interested || $this->config('request_headers')) && !empty($request->headers)) {
            $span->addTag('http.request.headers', $this->transformedHeaders($this->filterHeaders($request->headers)));
        }
        // 上报响应头
//        if (($this->interested || $this->config('response_headers')) && $this->isResponse($response) && !empty($response->headers)) {
//            $span->addTag('http.response.headers', $this->transformedHeaders($this->filterHeaders($response->headers)));
//        }

        // 上报请求请求
        if ($this->interested || $this->config('request_body')) {
            $maxSize = $this->config('request_body_max_size', 0);
            $bodySize = strlen($request->getContent());
            if ($maxSize > 0 && $bodySize <= $maxSize) {
                $span->addTag('http.request.size', $bodySize);
                $span->addTag('http.request.body', base64_encode(json_encode($this->filterInput($request->input()))));
            }
        }
        // 上报响应数据
//        if (($this->interested || $this->config('response_body')) && $this->isResponse($response) && $data = $response->content()) {
//            $replySize = strlen($data);
//            $maxSize = $this->config('response_body_max_size', 0);
//            if ($maxSize > 0 && $replySize <= $maxSize) {
//                $span->addTag('http.response.size', $replySize);
//                $span->addTag('http.response.body', base64_encode($data));
//            }
//        }
    }

    /**
     * @param array $headers
     * @return string
     */
    protected function transformedHeaders(array $headers = []): string
    {
        if (!$headers) {
            return '';
        }

        ksort($headers);
        $max = max(array_map('strlen', array_keys($headers))) + 1;

        $content = '';
        foreach ($headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));

            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }

        return base64_encode($content);
    }

    /**
     * @param HeaderBag $headers
     * @return array
     */
    protected function filterHeaders(HeaderBag $headers): array
    {
        return $this->hideSensitiveHeaders($this->filterAllowedHeaders(collect($headers)))->all();
    }

    protected function hideSensitiveHeaders(Collection $headers): Collection
    {
        $sensitiveHeaders = $this->config('sensitive_headers');

        $normalizedHeaders = array_map('strtolower', $sensitiveHeaders);

        $headers->transform(function ($value, $name) use ($normalizedHeaders) {
            return in_array($name, $normalizedHeaders)
                ? ['This value is hidden because it contains sensitive info']
                : $value;
        });

        return $headers;
    }

    /**
     * @param Collection $headers
     * @return Collection
     */
    protected function filterAllowedHeaders(Collection $headers): Collection
    {
        $allowedHeaders = $this->config('allowed_headers');

        if (in_array('*', $allowedHeaders)) {
            return $headers;
        }

        $normalizedHeaders = array_map('strtolower', $allowedHeaders);

        return $headers->filter(function ($value, $name) use ($normalizedHeaders) {
            return in_array($name, $normalizedHeaders);
        });
    }

    /**
     * @param array $input
     * @return array
     */
    protected function filterInput(array $input = []): array
    {
        return $this->hideSensitiveInput(collect($input))->all();
    }

    /**
     * @param Collection $input
     * @return Collection
     */
    protected function hideSensitiveInput(Collection $input): Collection
    {
        $sensitiveInput = $this->config('sensitive_input');

        $normalizedInput = array_map('strtolower', $sensitiveInput);

        $input->transform(function ($value, $name) use ($normalizedInput) {
            return in_array($name, $normalizedInput)
                ? ['This value is hidden because it contains sensitive info']
                : $value;
        });

        return $input;
    }

    /**
     * @param $route
     * @return bool
     */
    protected function isLaravelRoute($route): bool
    {
        return $route && method_exists($route, 'uri');
    }

    /**
     * @param $route
     * @return bool
     */
    protected function isLumenRoute($route): bool
    {
        return is_array($route) && is_array($route[2]);
    }

    /**
     * @param string $path
     * @param array $parameters
     * @return string
     */
    protected function getLumenRouteUri(string $path, array $parameters): string
    {
        $replaceMap = array_combine(
            array_values($parameters),
            array_map(function ($v) {
                return '{' . $v . '}';
            }, array_keys($parameters))
        );

        return strtr($path, $replaceMap);
    }

    /**
     * 标记进程信息
     * @param Span $span
     * @return void
     */
    protected function tagProcess(Span $span)
    {
        $span->setTags([
            ResourceAttributes::OS_DESCRIPTION => php_uname('r'),
            ResourceAttributes::OS_NAME => PHP_OS,
            ResourceAttributes::OS_VERSION => php_uname('v'),
            ResourceAttributes::HOST_NAME => php_uname('n'),
            ResourceAttributes::HOST_ARCH => php_uname('m'),
            ResourceAttributes::PROCESS_RUNTIME_NAME => php_sapi_name(),
            ResourceAttributes::PROCESS_RUNTIME_VERSION => PHP_VERSION,
            ResourceAttributes::PROCESS_PID => getmypid(),
            ResourceAttributes::PROCESS_EXECUTABLE_PATH => PHP_BINARY,
        ]);

        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        if (isset($_SERVER['argv']) ? $_SERVER['argv'] : null) {
            $span->setTags([
                ResourceAttributes::PROCESS_COMMAND => $_SERVER['argv'][0],
            ]);
        }

        /** @phan-suppress-next-line PhanTypeComparisonFromArray */
        if (extension_loaded('posix') && ($user = posix_getpwuid(posix_geteuid())) !== false) {
            $span->addTag(ResourceAttributes::PROCESS_OWNER, $user['name']);
        }
    }

    /**
     * 结束根跨度并清理
     */
    protected function finishRootSpan()
    {
        if ($this->tracer->getRootSpan() !== null) {
            $this->tracer->getRootSpan()->finish();
        }
        if ($this->tracer !== null) {
            $this->tracer->flush();
        }
    }
}