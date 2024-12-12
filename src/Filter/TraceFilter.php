<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Constant\Constant;
use Zipkin\Tags;
use Zipkin\Propagation\Map;
use JellyTony\Observability\Facades\Trace;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;

class TraceFilter implements Filter
{
    public function handle(Context $context, Closure $next, array $options = [])
    {
        if (empty(Trace::getTracer()) || empty(Trace::getRootSpan())) {
            return $next($context, $options);
        }

        // inject headers
        $headers = [];
        $spanName = sprintf("HTTP Client %s: %s", $context->getRequest()->getMethod(), $context->getRequest()->getUri()->getPath());
        $span = Trace::startSpan($spanName, Trace::getRootSpan()->getContext());
        $span->setKind("CLIENT");
        $span->tag(Tags\HTTP_METHOD, $context->getRequest()->getMethod());
        $span->tag(Tags\HTTP_PATH, $context->getRequest()->getUri()->getPath());
        $span->tag(Tags\HTTP_HOST, $context->getRequest()->getUri()->getHost());
        $span->tag(Tags\HTTP_URL, $context->getRequest()->getUri()->__toString());

        // 向  header 头注入 traceId 信息
        $injector = Trace::getPropagation()->getInjector(new Map());
        $h = [];

        $injector($span->getContext(), $h);
        foreach ($h as $k => $v) {
            $headers[] = $k . ":" . $v;
        }
        $context->getRequest()->setCurlHeaders($headers);
        try {
            $response = $next($context, $options);
            $this->terminate($context, $span);
            return $response;
        } catch (\Exception $e) {
            list($bizCode, $bizMsg) = convertExceptionToBizError($e);
            $context->setBizResult($bizCode, $bizMsg);
            $this->terminate($context, $span);
            // 继续抛出异常
            throw $e;
        }
    }

    public function terminate(Context $context, $span)
    {
        $span->tag(Tags\HTTP_STATUS_CODE, $context->getResponse()->getStatusCode());
        $span->tag(Tags\HTTP_RESPONSE_SIZE, $context->getResponse()->getBodySize());
        $span->tag(Constant::BIZ_CODE, $context->getBizCode());
        $span->tag(Constant::BIZ_MSG, $context->getBizMsg());
        if (!empty($context->getBizMsg()) && $context->getBizCode() > Constant::BIZ_CODE_SUCCESS) {
            $span->tag('error', $context->getBizMsg());
        }
        // 标志结束
        $span->finish();
    }
}