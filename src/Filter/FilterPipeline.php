<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Contracts\Context;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Context\Context as RawContext;

class FilterPipeline
{
    private $middlewares = [];
    private $finalHandler;

    public function __construct(Closure $finalHandler)
    {
        $this->finalHandler = $finalHandler;
    }

    // 添加中间件
    public function addMiddleware(Filter $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    // 执行中间件链
    public function handle(Context $context, array $options = [])
    {
        $next = $this->finalHandler;
        // 逆序遍历中间件，并包装闭包
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function (Context $context, array $options) use ($next, $middleware) {
                return $middleware->handle($context, $next, $options);
            };
        }

        // 最终执行处理器，传递 $context 和 $options
        return $next($context, $options);
    }

    // 静态方法：运行中间件链
    public static function run(Request $request, \Closure $finalHandler, array $middlewares = [], array $options = [])
    {
        $pipeline = new self($finalHandler);
        if (empty($middlewares)) {
            $middlewares = [
                \JellyTony\Observability\Filter\DefaultFilter::class,
                \JellyTony\Observability\Filter\Metadata::class,
                \JellyTony\Observability\Filter\TraceFilter::class,
                \JellyTony\Observability\Filter\LoggingFilter::class,
            ];
        }

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $middleware = new $middleware();
            }

            if ($middleware instanceof Filter) {
                $pipeline->addMiddleware($middleware);
            } else {
                throw new \InvalidArgumentException("Middleware must implement Filter");
            }
        }

        $context = new RawContext($request, null);
        return $pipeline->handle($context, $options);
    }
}