<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use JellyTony\Observability\Constant\Constant;
use JellyTony\Observability\Metadata\Metadata;

class ExceptionCapture
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Exception $e) {
            // 在这里做处理，如记录日志等
            $this->handleException($e);
            // 将异常重新抛出，继续传递给 Lumen 的默认异常处理器
            throw $e;
        }
    }

    /**
     * 处理捕获的异常
     */
    private function handleException(Exception $e)
    {
        \Log::error("Caught exception: " . $e->getCode() . "msg: " . $e->getMessage());

        $bizCode = 1004;
        if ($e->getCode() > 1000) {
            $bizCode = $e->getCode();
        }

        Metadata::set(Constant::BIZ_CODE, $bizCode);
        Metadata::set(Constant::BIZ_MSG, $e->getMessage());
    }
}