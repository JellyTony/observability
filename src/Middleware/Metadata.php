<?php

namespace JellyTony\Observability\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use JellyTony\Observability\Metadata\Metadata as RawMetadata;

class Metadata
{
    private $prefix;

    public function __construct()
    {
        $this->prefix = 'observability.middleware.server.metadata.';
    }

    /**
     * @param $key
     * @param $default
     * @return void
     */
    public function config($key = null, $default = null)
    {
        return config($this->prefix . $key, $default);
    }

    /**
     * 判断是否是自定义前缀
     * @param string $key
     * @return bool
     */
    public function hasPrefix(string $key): bool
    {
        $prefixes = $this->config('prefix', ["x-md-"]); // x-md-global-, x-md-local
        foreach ($prefixes as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $headers = $request->headers->all();
        foreach ($headers as $key => $values) {
            if ($this->hasPrefix($key)) {
                foreach ($values as $value) {
                    RawMetadata::set($key, $value);
                }
            }
        }

        return $next($request);
    }
}