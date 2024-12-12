<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use JellyTony\Observability\Constant\Constant;
use Zipkin\Propagation\Id;
use Illuminate\Http\Request;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
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
            $_SERVER[Constant::HTTP_X_REQUEST_ID] = uuidV4();
        }
        // 注入 header 头x-b3-traceid
        if (!isset($_SERVER[Constant::HTTP_X_B3_TRACE_ID]) || empty($_SERVER[Constant::HTTP_X_B3_TRACE_ID])) {
            $_SERVER[Constant::HTTP_X_B3_TRACE_ID] = Id\generateNextId();
        }

        return $next($request);
    }
}

function uuidV4()
{
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
}
