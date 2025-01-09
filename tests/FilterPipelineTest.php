<?php
namespace JellyTony\Observability\Tests;

use Closure;
use JellyTony\Observability\Filter\FilterPipeline;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;
use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Context\Context as RawContext;
use PHPUnit\Framework\TestCase;

class FilterPipelineTest extends TestCase
{
    public function testHandleWithSingleMiddleware()
    {
        // 创建 Mock 中间件
        $middleware = $this->createMock(Filter::class);
        $middleware->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Context $context, Closure $next, array $options) {
                return $next($context, $options);
            });

        // 创建最终处理器
        $finalHandler = function (Context $context, array $options) {
            return 'FinalResult';
        };

        // 初始化管道
        $pipeline = new FilterPipeline($finalHandler);
        $pipeline->addMiddleware($middleware);

        $context = $this->createMock(Context::class);

        // 执行测试
        $result = $pipeline->handle($context);

        $this->assertEquals('FinalResult', $result);
    }

    public function testHandleWithMultipleMiddlewares()
    {
        // 创建 Mock 中间件
        $middleware1 = $this->createMock(Filter::class);
        $middleware1->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Context $context, Closure $next, array $options) {
                $options['step'][] = 'middleware1';
                return $next($context, $options);
            });

        $middleware2 = $this->createMock(Filter::class);
        $middleware2->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Context $context, Closure $next, array $options) {
                $options['step'][] = 'middleware2';
                return $next($context, $options);
            });

        // 创建最终处理器
        $finalHandler = function (Context $context, array $options) {
            $options['step'][] = 'finalHandler';
            return $options['step'];
        };

        // 初始化管道
        $pipeline = new FilterPipeline($finalHandler);
        $pipeline->addMiddleware($middleware1);
        $pipeline->addMiddleware($middleware2);

        $context = $this->createMock(Context::class);

        // 执行测试
        $result = $pipeline->handle($context, ['step' => []]);

        $this->assertEquals(['middleware1', 'middleware2', 'finalHandler'], $result);
    }

    public function testRunWithStaticMethod()
    {
        // 创建 Mock 请求
        $request = $this->createMock(Request::class);

        // 创建最终处理器
        $finalHandler = function (Context $context, array $options) {
            return 'StaticFinalResult';
        };

        // 模拟中间件
        $middleware = $this->createMock(Filter::class);
        $middleware->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Context $context, Closure $next, array $options) {
                return $next($context, $options);
            });

        // 执行静态方法
        $result = FilterPipeline::run($request, $finalHandler, [$middleware]);

        $this->assertEquals('StaticFinalResult', $result);
    }

    public function testRunWithInvalidMiddleware()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Middleware must implement Filter");

        // 创建 Mock 请求
        $request = $this->createMock(Request::class);

        // 执行静态方法，传入无效中间件
        FilterPipeline::run($request, function () {}, ['InvalidMiddleware']);
    }
}