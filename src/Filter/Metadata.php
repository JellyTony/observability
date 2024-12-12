<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;

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

    public function handle(Context $context, Closure $next, array $options = [])
    {
        // 在客户端请求时添加元数据到请求头
        $this->injectMetadataToRequest($context->getRequest());

        // 处理请求并获取响应
        return $next($context, $options);
    }

    // 客户端处理请求时，注入元数据
    private function injectMetadataToRequest(Request $request)
    {
        if (!empty($this->constants)) {
            $request->setHeaders($this->constants);
        }

        // x-md-global-
        $headers = $this->getAllHeaders();
        foreach ($headers as $key => $value) {
            if ($this->hasPrefix($key)) {
                $request->setHeader($key, $value);
            }
        }
    }


    // 获取全局的所有请求头
    private function getAllHeaders()
    {
        // 通过 getallheaders() 获取所有 HTTP 请求头
        // 这依赖于 PHP 环境是否支持该函数 (例如在 Apache 中)
        if (function_exists('getallheaders')) {
            return getallheaders();  // 返回一个关联数组，包含所有请求头
        }

        // 如果无法使用 getallheaders()，使用 $_SERVER 作为备选方案
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            // 检查是否是 HTTP 请求头
            if (strpos($key, 'HTTP_') === 0) {
                $headerKey = str_replace('HTTP_', '', $key);  // 去掉 HTTP_ 前缀
                $headerKey = str_replace('_', '-', strtolower($headerKey));  // 转换为标准的请求头格式
                $headers[$headerKey] = $value;
            }
        }
        return $headers;
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
}