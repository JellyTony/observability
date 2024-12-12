<?php

namespace JellyTony\Observability\Util;

class HeaderFilter
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 过滤头部信息
     * @param array $headers
     * @return array
     */
    public function filterHeaders(array $headers): array
    {
        return $this->hideSensitiveHeaders($this->filterAllowedHeaders($headers));
    }

    /**
     * 过滤允许的头部
     * @param array $headers
     * @return array
     */
    protected function filterAllowedHeaders(array $headers): array
    {
        $allowedHeaders = $this->config['allowed_headers'] ?? [];

        if (in_array('*', $allowedHeaders)) {
            return $headers;
        }

        $normalizedHeaders = array_map('strtolower', $allowedHeaders);

        return array_filter($headers, function ($value, $name) use ($normalizedHeaders) {
            return in_array(strtolower($name), $normalizedHeaders);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * 隐藏敏感的头部
     * @param array $headers
     * @return array
     */
    protected function hideSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = $this->config['sensitive_headers'] ?? [];
        $normalizedHeaders = array_map('strtolower', $sensitiveHeaders);

        foreach ($headers as $name => &$value) {
            if (in_array(strtolower($name), $normalizedHeaders)) {
                $value = 'This value is hidden because it contains sensitive info';
            }
        }

        return $headers;
    }

    /**
     * 格式化头部为字符串
     * @param array $headers
     * @return string
     */
    public function transformedHeaders(array $headers): string
    {
        if (empty($headers)) {
            return '';
        }

        ksort($headers);
        $max = max(array_map('strlen', array_keys($headers))) + 1;

        $content = '';
        foreach ($headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));

            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }

        return $content;
    }

    /**
     * 过滤输入信息
     * @param array $input
     * @return array
     */
    public function filterInput(array $input): array
    {
        return $this->hideSensitiveInput($input);
    }

    /**
     * 隐藏敏感的输入
     * @param array $input
     * @return array
     */
    protected function hideSensitiveInput(array $input): array
    {
        $sensitiveInput = $this->config['sensitive_input'] ?? [];
        $normalizedInput = array_map('strtolower', $sensitiveInput);

        foreach ($input as $name => &$value) {
            if (in_array(strtolower($name), $normalizedInput)) {
                $value = 'This value is hidden because it contains sensitive info';
            }
        }

        return $input;
    }
}