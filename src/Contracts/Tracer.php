<?php

namespace JellyTony\Observability\Contracts;

use Zipkin\Tracing as Base;
use Zipkin\Propagation\SamplingFlags;

interface Tracer extends Base
{

    /**
     * Start a new span based on a parent trace context. The context may come either from
     * external source (extracted from HTTP request, AMQP message, etc., see extract method)
     * or received from another span in this service.
     *
     * If parent context does not contain a trace, a new trace will be implicitly created.
     *
     * @param string $name
     * @param SamplingFlags|null $spanContext
     * @param int|null $timestamp intval(microtime(true) * 1000000)
     * @return Span
     */
    public function startSpan(string $name, SamplingFlags $spanContext = null, int $timestamp = null): Span;

    /**
     * Retrieve the root span of the service
     *
     * @return Span|null
     */
    public function getRootSpan(): ?Span;

    /**
     * Retrieve the most recently activated span.
     *
     * @return Span|null
     */
    public function getCurrentSpan(): ?Span;


    /**
     * Calling this will flush any pending spans to the transport and reset the state of the tracer.
     * Make sure this method is called after the request is finished.
     */
    public function flush(): void;
}
