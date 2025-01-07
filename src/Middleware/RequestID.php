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

    public function handle(Request $request, Closure $next)
    {
        $callerService = $request->header('x-md-local-caller_service', 'unknown');
        // 兼容swoole的问题
        if (isset($_SERVER['HTTP_X-REQUEST-ID']) && !empty($_SERVER['HTTP_X-REQUEST-ID'])) {
            $_SERVER[Constant::HTTP_X_REQUEST_ID] = $_SERVER['HTTP_X-REQUEST-ID'];
        }
        if (isset($_SERVER['HTTP_X-B3-TRACEID']) && !empty($_SERVER['HTTP_X-B3-TRACEID'])) {
            $_SERVER[Constant::HTTP_X_B3_TRACE_ID] = $_SERVER['HTTP_X-B3-TRACEID'];
        }
        if (isset($_SERVER['HTTP_MP-DEBUG']) && !empty($_SERVER['HTTP_MP-DEBUG'])) {
            $_SERVER[Constant::HTTP_MP_DEBUG] = $_SERVER['HTTP_MP-DEBUG'];
        }

        // 注入header头 x-request-id
        if (!isset($_SERVER[Constant::HTTP_X_REQUEST_ID]) || empty($_SERVER[Constant::HTTP_X_REQUEST_ID])) {
            if(!$this->shouldBeExcluded($request->path())) {
                \Log::debug('need trace request_id request from ' . $callerService, ['global_fields' => [
                    "caller" => $callerService,
                    'route' => $request->path(),
                ]]);
            }
            $_SERVER[Constant::HTTP_X_REQUEST_ID] = uuidV4();
        }
        // 注入 header 头x-b3-traceid
        if (!isset($_SERVER[Constant::HTTP_X_B3_TRACE_ID]) || empty($_SERVER[Constant::HTTP_X_B3_TRACE_ID])) {
            if(!$this->shouldBeExcluded($request->path())) {
                \Log::debug('need trace trace_id request from ' . $callerService, ['global_fields' => [
                    "caller" => $callerService,
                    'route' => $request->path(),
                ]]);
            }
            $_SERVER[Constant::HTTP_X_B3_TRACE_ID] = Id\generateNextId();
        }

        return $next($request);
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
