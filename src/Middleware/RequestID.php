<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JellyTony\Observability\Constant\Constant;
use Zipkin\Propagation\Id;

class RequestID
{
    public $excluded_paths = [
        'listen',
        'metrics'
    ];

    protected const SWOOLE_HEADERS_MAP = [
        'HTTP_X-REQUEST-ID' => Constant::HTTP_X_REQUEST_ID,
        'HTTP_X-B3-TRACEID' => Constant::HTTP_X_B3_TRACE_ID,
        'HTTP_X-B3-SPANID' => Constant::HTTP_X_B3_SPAN_ID,
        'HTTP_X-B3-PARENTSPANID' => Constant::HTTP_X_B3_PARENT_SPAN_ID,
        'HTTP_X-B3-SAMPLED' => Constant::HTTP_X_B3_SAMPLED,
        'HTTP_X-B3-FLAGS' => Constant::HTTP_X_B3_FLAGS,
        'HTTP_MP-DEBUG' => Constant::HTTP_MP_DEBUG,
    ];

    // 判断是否是swoole请求头
    public function hasSwooleRequestHeader($key)
    {
        return isset($_SERVER[$key]) && !empty($_SERVER[$key]);
    }

    public function handle(Request $request, Closure $next)
    {
        $callerService = $request->header('x-md-local-caller_service', 'unknown');
        // 统一处理 Swoole 请求头
        $this->handleSwooleHeaders($request);

        // 注入header头 x-request-id
        if (!isset($_SERVER[Constant::HTTP_X_REQUEST_ID]) || empty($_SERVER[Constant::HTTP_X_REQUEST_ID])) {
            if (!$this->shouldBeExcluded($request->path())) {
                \Log::debug('need trace request_id request from ' . $callerService, ['global_fields' => [
                    "caller" => $callerService,
                    'route' => $request->path(),
                ]]);
            }
//            $_SERVER[Constant::HTTP_X_REQUEST_ID] = uuidV4();
        }
        // 注入 header 头x-b3-traceid
        if (!isset($_SERVER[Constant::HTTP_X_B3_TRACE_ID]) || empty($_SERVER[Constant::HTTP_X_B3_TRACE_ID])) {
            if (!$this->shouldBeExcluded($request->path())) {
                \Log::debug('need trace trace_id request from ' . $callerService, ['global_fields' => [
                    "caller" => $callerService,
                    'route' => $request->path(),
                ]]);
            }
            $_SERVER[Constant::HTTP_X_B3_TRACE_ID] = Id\generateNextId();
        }

        return $next($request);
    }

    protected function handleSwooleHeaders(Request $request)
    {
        foreach (self::SWOOLE_HEADERS_MAP as $swooleKey => $constantKey) {
            if ($this->hasSwooleRequestHeader($swooleKey)) {
                $_SERVER[$constantKey] = $_SERVER[$swooleKey];
                $request->headers->set($constantKey, $_SERVER[$swooleKey]);
            }
        }
    }

    protected function shouldBeExcluded(string $path): bool
    {
        foreach ($this->excluded_paths as $excludedPath) {
            if (Str::is($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }
}

function uuidV4()
{
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
}
