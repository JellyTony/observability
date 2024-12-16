<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;
use JellyTony\Observability\Metadata\Metadata as RawMetadata;

class Metadata implements Filter
{
    /**
     * @var array
     */
    private $prefix;
    /**
     * @var array
     */
    private $constants;

    public function __construct(array $prefix = [], array $constants = [])
    {
        if (empty($prefix)) {
            $prefix = [
                'x-md-global-',
            ];
        }
        $this->prefix = $prefix;
        $this->constants = $constants;
    }


    // 检查是否有指定的前缀
    private function hasPrefix(string $key): bool
    {
        $key = strtolower($key);
        foreach ($this->prefix as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    public function handle(Context $context, Closure $next, array $options = [])
    {
        $headers = [];
        // x-md-local-
        if (!empty($this->constants)) {
            $headers = $this->constants;
        }

        // x-md-global-
        $mds = RawMetadata::getAll();
        foreach ($mds as $key => $values) {
            if ($this->hasPrefix($key)) {
                foreach ($values as $value)
                $headers[$key] = $value;
            }
        }

        if (!empty($headers)) {
            $context->getRequest()->setHeaders($headers);
        }

        // 处理请求并获取响应
        return $next($context, $options);
    }
}