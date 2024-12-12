<?php

namespace JellyTony\Observability\Drivers\Null;

use Zipkin\Propagation\Propagation;
use Zipkin\Propagation\SamplingFlags;
use JellyTony\Observability\Contracts\Span;
use JellyTony\Observability\Contracts\Tracer;
use Zipkin\Reporters\Noop;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Tracer as RawTracer;
use Zipkin\TracingBuilder;

class NullTracer implements Tracer
{
    /**
     * @var NullSpan|null
     */
    protected $currentSpan;

    /**
     * @var NullSpan|null
     */
    protected $rootSpan;

    /**
     * @var \Zipkin\DefaultTracing|RawTracer
     */
    protected $tracing;


    /**
     * Initialize tracer based on parameters provided during object construction
     *
     * @return Tracer
     */
    public function init(): Tracer
    {
        $sampler = BinarySampler::createAsNeverSample();
        $this->tracing = TracingBuilder::create()
            ->havingSampler($sampler)
            ->havingReporter(new Noop())
            ->build();

        return $this;
    }

    /**
     * Start a new span based on a parent trace context. The context may come either from
     * external source (extracted from HTTP request, AMQP message, etc., see extract method)
     * or received from another span in this service.
     *
     * If parent context does not contain a trace, a new trace will be implicitly created.
     *
     * @param string $name
     * @param SamplingFlags|null $contextOrFlags
     * @param int|null $timestamp intval(microtime(true) * 1000000)
     * @return Span
     */
    public function startSpan(string $name, SamplingFlags $contextOrFlags = null, ?int $timestamp = null): Span
    {
        if ($this->rootSpan) {
            $span = new NullSpan(false);
        } else {
            $span = new NullSpan(true);
            $this->rootSpan = $span;
        }

        $this->currentSpan = $span;

        return $span;
    }

    /**
     * Retrieve the root span of the service
     *
     * @return Span|null
     */
    public function getRootSpan(): Span
    {
        return $this->rootSpan;
    }

    /**
     * Retrieve the most recently activated span.
     *
     * @return Span|null
     */
    public function getCurrentSpan(): Span
    {
        return $this->currentSpan;
    }

    /**
     * All tracing commands start with a {@link Span}. Use a tracer to create spans.
     *
     * @return RawTracer
     */
    public function getTracer(): RawTracer {
        return $this->tracing->getTracer();
    }

    /**
     * When a trace leaves the process, it needs to be propagated, usually via headers. This utility
     * is used to inject or extract a trace context from remote requests.
     *
     * @return Propagation
     */
    public function getPropagation(): Propagation {
        return $this->tracing->getPropagation();
    }

    /**
     * When true, no recording is done and nothing is reported to zipkin. However, trace context is
     * still injected into outgoing requests.
     *
     * @return bool
     * @see Span#isNoop()
     */
    public function isNoop(): bool {
        return $this->tracing->isNoop();
    }

    /**
     * Calling this will flush any pending spans to the transport and reset the state of the tracer.
     * Make sure this method is called after the request is finished.
     */
    public function flush(): void
    {
        $this->rootSpan = null;
        $this->currentSpan = null;
    }
}
