<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;

class DefaultFilter implements Filter
{

    public function handle(Context $context, Closure $next, array $options = [])
    {
        $headers = [];
        if (isset($_SERVER[Constant::HTTP_X_REQUEST_ID])) {
            $headers['X-Request-Id'] = $_SERVER[Constant::HTTP_X_REQUEST_ID];
        }
        if (isset($_SERVER[Constant::HTTP_X_B3_TRACE_ID])) {
            $headers['X-B3-TraceId'] = $_SERVER[Constant::HTTP_X_B3_TRACE_ID];
        }
        if (isset($_SERVER[Constant::HTTP_MP_DEBUG])) {
            $headers['Mp-Debug'] = '1';
        }
        $context->getRequest()->setHeaders($headers);

        return $next($context, $options);
    }
}