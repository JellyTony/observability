<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Metadata\Metadata;
use Zipkin\Propagation\Map;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use JellyTony\Observability\Contracts\Span;
use JellyTony\Observability\Contracts\Tracer;
use Illuminate\Contracts\Config\Repository;
use Symfony\Component\HttpFoundation\HeaderBag;

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

    private $debug;

    private $prefix;

    /**
     * TraceRequests constructor.
     * @param Tracer $tracer
     * @param Repository $config
     */
    public function __construct(Tracer $tracer, Repository $config)
    {
        $this->prefix = 'observability.middleware.trace.';
        $this->tracer = $tracer;
        $this->config = $config;
        $this->debug = $this->config->get('observability.middleware.debug');
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
        if ($this->shouldBeExcluded($request->path())) {
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

        $this->tagRequestData($span, $request);

        $reply = $next($request);
        if (!empty($reply) && $reply instanceof JsonResponse) {
            // 获取 JSON 响应的数据
            $responseData = $reply->getData(true);  // 将数据获取为数组

            // 添加 trace_id 和 biz_code 到响应数据
            if (!empty($traceID) && !empty($responseData)) {
                $responseData[Constant::TRACE_ID] = $traceID;
                Metadata::set(Constant::TRACE_ID, $traceID);
                $reply->setData($responseData);  // 将修改后的数据重新设置到响应中
            }

            // 设置 biz_code 和 biz_msg（如果响应数据中包含这些字段）
            if (!empty($responseData['code'])) {
                Metadata::set(Constant::BIZ_CODE, $responseData['code']);
            }
            if (!empty($responseData['msg'])) {
                Metadata::set(Constant::BIZ_MSG, $responseData['msg']);
            }
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        $latency = round($duration, 2);


        if ($latency > $this->config->get($this->prefix . 'latency_threshold') || isMpDebug()) {
            $this->debug = true;
        }
        if ($span->getContext()->isSampled() || $latency > $this->config->get($this->prefix . 'latency_threshold') || isMpDebug()) {
            $span->getContext()->withSampled(true);
        }

        $this->terminate($request, $reply);

        $this->finishRootSpan();

        return $reply;
    }

    /**
     * 结束根跨度并清理
     */
    protected function finishRootSpan()
    {
        if ($this->tracer->getRootSpan() !== null) {
            $this->tracer->getRootSpan()->finish();
            $this->tracer->flush();
        }
    }

    /**
     * @param Request $request
     * @param Response|JsonResponse $response
     */
    public function terminate(Request $request, $response)
    {
        $span = $this->tracer->getRootSpan();
        if (!empty($span)) {
            $this->tagResponseData($span, $request, $response);

            $route = $request->route();

            if ($this->isLaravelRoute($route)) {
                $span->setName(sprintf('HTTP Server %s: %s', $request->method(), $request->route()->uri()));
            }

            if ($this->isLumenRoute($route)) {
                $routeUri = $this->getLumenRouteUri($request->path(), $route[2]);
                $span->setName(sprintf('HTTP Server %s: %s', $request->method(), $routeUri));
            }

            $span->tag(Constant::BIZ_CODE, bizCode());
            if (bizCode() > Constant::BIZ_CODE_SUCCESS) {
                $span->tag("error", bizMsg());
            }
        }
    }

    /**
     * @param Request $request
     * @param Span $span
     */
    protected function tagRequestData(Span $span, Request $request): void
    {
        $span->tag('type', 'http');
        $span->setKind("SERVER");
        $span->tag("http.client_ip", $request->ip());
        $span->tag("http.user_agent", $request->userAgent());
        $span->tag("caller.service_name", $request->header('x-md-local-caller_service', 'unknown'));
        $span->tag("http.method", $request->getMethod());
        $span->tag("http.url", $request->fullUrl());
        $span->tag('http.route', $request->path());
        if ($this->debug) {
            $span->tag('http.request.headers', $this->transformedHeaders($this->filterHeaders($request->headers)));
        }
        if ($this->debug && in_array($request->headers->get('Content_Type'), $this->config->get($this->prefix . 'payload.content_types'))) {
            $span->tag('http.request.body', json_encode($this->filterInput($request->input())));
        }
    }

    /**
     * @param Span $span
     * @param Request $request
     * @param Response|JsonResponse $response
     */
    protected function tagResponseData(Span $span, Request $request, $response): void
    {
        if ($route = $request->route()) {
            if (method_exists($route, 'getActionName')) {
                $span->tag('laravel_action', $route->getActionName());
            }
        }

        $span->tag('http.status_code', strval($response->getStatusCode()));
        if ($this->debug) {
            $span->tag('http.response.headers', $this->transformedHeaders($this->filterHeaders($response->headers)));
        }
        if ($this->debug && in_array($response->headers->get('Content_Type'), $this->config->get($this->prefix . 'payload.content_types'))) {
            $data = $response->content();
            $span->tag('http.response.body', $data);
            $span->tag('http.response.size', strlen($data));
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function shouldBeExcluded(string $path): bool
    {
        foreach ($this->config->get($this->prefix . 'excluded_paths') as $excludedPath) {
            if (Str::is($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param HeaderBag $headers
     * @return array
     */
    protected function filterHeaders(HeaderBag $headers): array
    {
        return $this->hideSensitiveHeaders($this->filterAllowedHeaders(collect($headers)))->all();
    }

    /**
     * @param Collection $headers
     * @return Collection
     */
    protected function filterAllowedHeaders(Collection $headers): Collection
    {
        $allowedHeaders = $this->config->get($this->prefix . 'allowed_headers');

        if (in_array('*', $allowedHeaders)) {
            return $headers;
        }

        $normalizedHeaders = array_map('strtolower', $allowedHeaders);

        return $headers->filter(function ($value, $name) use ($normalizedHeaders) {
            return in_array($name, $normalizedHeaders);
        });
    }

    protected function hideSensitiveHeaders(Collection $headers): Collection
    {
        $sensitiveHeaders = $this->config->get($this->prefix . 'sensitive_headers');

        $normalizedHeaders = array_map('strtolower', $sensitiveHeaders);

        $headers->transform(function ($value, $name) use ($normalizedHeaders) {
            return in_array($name, $normalizedHeaders)
                ? ['This value is hidden because it contains sensitive info']
                : $value;
        });

        return $headers;
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

        return $content;
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
        $sensitiveInput = $this->config->get($this->prefix . 'sensitive_input');

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
}
