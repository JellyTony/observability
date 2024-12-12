<?php

namespace JellyTony\Observability\Drivers\Zipkin;

use Zipkin\Sampler;
use Zipkin\Endpoint;
use Zipkin\Reporter;
use Zipkin\TracingBuilder;
use Zipkin\Tracer as RawTracer;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Propagation\Propagation;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Reporters\Http as HttpReporter;

use JellyTony\Observability\Contracts\Span;
use JellyTony\Observability\Contracts\Tracer;

class ZipkinTracer implements Tracer
{
    /**
     * @var int
     */
    protected static $maxTagLen = 1048576;

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var string
     */
    protected $endpointUrl;

    /**
     * @var bool
     */
    protected $usesTraceId128bits;

    /**
     * @var int|null
     */
    protected $requestTimeout;

    /**
     * @var Reporter|null
     */
    protected $reporter;

    /**
     * @var Sampler|null
     */
    protected $sampler;

    /**
     * @var \Zipkin\DefaultTracing|RawTracer
     */
    protected $tracing;

    /**
     * @var Span|null
     */
    protected $rootSpan;

    /**
     * @var Span|null
     */
    protected $currentSpan;

    /**
     * ZipkinTracer constructor.
     * @param string $serviceName
     * @param string $endpointUrl
     * @param bool|null $usesTraceId128bits
     * @param int|null $requestTimeout
     * @param Reporter|null $reporter
     * @param Sampler|null $sampler
     */
    public function __construct(
        string   $serviceName,
        string   $endpointUrl,
        bool     $usesTraceId128bits = false,
        int      $requestTimeout = 5,
        Reporter $reporter = null,
        Sampler  $sampler = null
    )
    {
        $this->serviceName = $serviceName;
        $this->endpointUrl = $endpointUrl;
        $this->usesTraceId128bits = $usesTraceId128bits;
        $this->requestTimeout = $requestTimeout;
        $this->reporter = $reporter;
        $this->sampler = $sampler;
    }

    /**
     * @return int
     */
    public static function getMaxTagLen(): int
    {
        return self::$maxTagLen;
    }

    /**
     * @param int $maxTagLen
     */
    public static function setMaxTagLen(int $maxTagLen)
    {
        self::$maxTagLen = $maxTagLen;
    }

    /**
     * Initialize tracer based on parameters provided during object construction
     *
     * @return Tracer
     */
    public function init(): Tracer
    {
        $this->tracing = TracingBuilder::create()
            ->havingLocalEndpoint($this->createEndpoint())
            ->havingTraceId128bits($this->usesTraceId128bits)
            ->havingSampler($this->createSampler())
            ->havingReporter($this->createReporter())
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
     * The first span you create in the service will be considered the root span. Calling
     * flush {@see ZipkinTracer::flush()} will unset the root span along with request uuid.
     *
     * @param string $name
     * @param SamplingFlags|null $contextOrFlags
     * @param int|null $timestamp intval(microtime(true) * 1000000)
     * @return Span
     */
    public function startSpan(string $name, SamplingFlags $contextOrFlags = null, int $timestamp = null): Span
    {
        $rawSpan = $this->tracing->getTracer()->nextSpan($contextOrFlags ? $contextOrFlags : null);
        if ($this->rootSpan) {
            $span = new ZipkinSpan($rawSpan, false);
        } else {
            $span = new ZipkinSpan($rawSpan, true);
            $this->rootSpan = $span;
        }

        $this->currentSpan = $span;
        $span->setName($name);

        $rawSpan->start($timestamp);

        return $span;
    }

    /**
     * Retrieve the root span of the service
     *
     * @return Span|null
     */
    public function getRootSpan():?Span
    {
        return $this->rootSpan;
    }

    /**
     * Retrieve the most recently activated span.
     *
     * @return Span|null
     */
    public function getCurrentSpan(): ?Span
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
        $this->tracing->getTracer()->flush();
        $this->rootSpan = null;
        $this->currentSpan = null;
    }

    /**
     * @return Reporter
     */
    protected function createReporter(): Reporter
    {
        if (!$this->reporter) {
            $curlFactory = HttpReporter\CurlFactory::create();
            $this->reporter = new HttpReporter($curlFactory, [
                'endpoint_url' => $this->endpointUrl,
                'timeout' => $this->requestTimeout,
            ]);
        }

        return $this->reporter;
    }

    /**
     * @return Endpoint
     */
    protected function createEndpoint(): Endpoint
    {
        return Endpoint::createFromGlobals()->withServiceName($this->serviceName);
    }

    /**
     * @return Sampler
     */
    protected function createSampler(): Sampler
    {
        if (!$this->sampler) {
            $this->sampler = BinarySampler::createAsAlwaysSample();
        }

        return $this->sampler;
    }
}
