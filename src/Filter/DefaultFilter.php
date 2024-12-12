<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;
use JellyTony\Observability\Metadata\Metadata;

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

        try {
            return $next($context, $options);
        } catch (\Exception $e) {
            // 在这里做处理，如记录日志等
            $this->handleException($context, $e);
            // 继续抛出异常
            throw $e;
        }
    }

    private function handleException(Context $context, \Exception $e)
    {
        \Log::error("Caught exception: " . $e->getCode() . "msg: " . $e->getMessage());

        $bizCode = 1004;
        if ($e->getCode() > 1000) {
            $bizCode = $e->getCode();
        }

        $context->setBizResult($bizCode, $e->getMessage());
        Metadata::set(Constant::BIZ_CODE, $bizCode);
        Metadata::set(Constant::BIZ_MSG, $e->getMessage());
    }
}