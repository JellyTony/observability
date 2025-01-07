<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Illuminate\Http\Request;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Util\HeaderFilter;
use Zipkin\Propagation\Id;

class RequestID
{
    private $headerFilter;


    /**
     * TraceRequests constructor.
     */
    public function __construct()
    {
        $this->headerFilter = new HeaderFilter([
            'allowed_headers' => ['*'], // 允许的头部
            'sensitive_headers' => [], // 敏感的头部
            'sensitive_input' => [],  // 敏感的输入
        ]);
    }

    public function handle(Request $request, Closure $next)
    {
        $fields = [];
        $fields['path'] = $request->path();
        $fields['header'] = $this->headerFilter->transformedHeaders($this->headerFilter->filterHeaders($request->headers->all()));
        \Log::debug('httpraw header', ['global_fields' => $fields]);

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
