<?php

namespace JellyTony\Observability\Facades;

use Zipkin\Tracer as RawTracer;
use Zipkin\Propagation\Propagation;
use Zipkin\Propagation\TraceContext;
use Illuminate\Support\Facades\Facade;
use JellyTony\Observability\Contracts\Span;
use JellyTony\Observability\Contracts\Tracer;

/**
 * @see \JellyTony\Observability\Contracts\Tracer
 *
 * @method static Span startSpan(string $name, TraceContext $spanContext = null, int $timestamp = null)
 * @method static Span getRootSpan()
 * @method static Span getCurrentSpan()
 * @method static RawTracer getTracer()
 * @method static Propagation getPropagation()
 * @method static bool isNoop()
 * @method static void flush()
 */
class Trace extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor(): string
    {
        return Tracer::class;
    }
}
