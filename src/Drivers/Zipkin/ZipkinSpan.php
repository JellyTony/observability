<?php

namespace JellyTony\Observability\Drivers\Zipkin;

use JellyTony\Observability\Contracts\Span;
use Throwable;
use Zipkin\Endpoint;
use Zipkin\Propagation\TraceContext;
use Zipkin\Span as RawSpan;

class ZipkinSpan implements Span
{
    /**
     * @var RawSpan
     */
    protected $span;

    /**
     * @var bool
     */
    protected $isRoot;

    /**
     * ZipkinSpan constructor.
     * @param RawSpan $span
     * @param bool $isRoot
     */
    public function __construct(RawSpan $span, bool $isRoot)
    {
        $this->span = $span;
        $this->isRoot = $isRoot;
    }

    public function isNoop(): bool
    {
        return $this->span->isNoop();
    }

    /**
     * @return TraceContext
     */
    public function getContext(): TraceContext
    {
        return $this->span->getContext();
    }

    /**
     * Starts the span with an implicit timestamp.
     *
     * Spans can be modified before calling start. For example, you can add tags to the span and
     * set its name without lock contention.
     *
     * @param int|null $timestamp
     * @return void
     */
    public function start(int $timestamp = null): void
    {
        $this->span->start($timestamp);
    }

    /**
     * Sets the string name for the logical operation this span represents.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->span->setName($name);
    }

    /**
     * The kind of span is optional. When set, it affects how a span is reported. For example, if the
     * kind is {@link SERVER}, the span's start timestamp is implicitly annotated as "sr"
     * and that plus its duration as "ss".
     *
     * The value must be strictly one of the ones listed in {@link Kind}.
     *
     * @param string $kind
     * @return void
     */
    public function setKind(string $kind): void
    {
        $this->span->setKind($kind);
    }

    public function addTag($key, $val): void
    {
        if (empty($key) || empty($val)) {
            return;
        }

        $this->tag($key, $val);
    }

    /**
     * Tags give your span context for search, viewing and analysis. For example, a key
     * "your_app.version" would let you lookup spans by version. A tag {@link Zipkin\Tags\SQL_QUERY}
     * isn't searchable, but it can help in debugging when viewing a trace.
     *
     * @param string $key Name used to lookup spans, such as "your_app.version". See {@link Zipkin\Tags} for
     * standard ones.
     * @param string $value
     * @return void
     */
    public function tag(string $key, string $value): void
    {
        if (empty($key) || empty($value)) {
            return;
        }
        if (is_numeric($value)) {
            $value = strval($value);
        }
        $this->span->tag($key, $value);
    }

    /**
     * Adds multiple tags at once.
     * @param array $values
     * @return void
     */
    public function setTags(array $values): void
    {
        foreach ($values as $k => $v) {
            if (empty($k) || empty($v)) {
                continue;
            }
            if (is_array($v)) {
                $v = json_encode($v);
            }

            $this->tag($k, $v);
        }
    }

    /**
     * Adds tags depending on the configured {@link TraceFilter::errorParser() error parser}
     * @param Throwable $e
     */
    public function setError(Throwable $e): void
    {
        $this->span->setError($e);
    }

    /**
     * Associates an event that explains latency with the current system time.
     *
     * @param string $value A short tag indicating the event, like "finagle.retry"
     * @param int|null $timestamp
     * @return void
     * @see Annotations
     */
    public function annotate(string $value, int $timestamp = null): void
    {
        $this->span->annotate($value, $timestamp);
    }

    /**
     * For a client span, this would be the server's address.
     *
     * It is often expensive to derive a remote address: always check {@link #isNoop()} first!
     *
     * @param Endpoint $remoteEndpoint
     * @return void
     */
    public function setRemoteEndpoint(Endpoint $remoteEndpoint): void
    {
        $this->span->setRemoteEndpoint($remoteEndpoint);
    }

    /**
     * Throws away the current span without reporting it.
     *
     * @return void
     */
    public function abandon(): void
    {
        $this->span->abandon();
    }

    /**
     * Like {@link #finish()}, except with a given timestamp in microseconds.
     *
     * {@link zipkin.Span#duration Zipkin's span duration} is derived by subtracting the start
     * timestamp from this, and set when appropriate.
     *
     * @param int|null $timestamp
     * @return void
     */
    public function finish(int $timestamp = null): void
    {
        $this->span->finish($timestamp);
    }

    /**
     * Reports the span, even if unfinished. Most users will not call this method.
     *
     * This primarily supports two use cases: one-way spans and orphaned spans.
     * For example, a one-way span can be modeled as a span where one tracer calls start and another
     * calls finish. In order to report that span from its origin, flush must be called.
     *
     * Another example is where a user did not call finish within a deadline or before a shutdown
     * occurs. By flushing, you can report what was in progress.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->span->flush();
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->isRoot;
    }
}
