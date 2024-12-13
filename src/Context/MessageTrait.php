<?php

namespace JellyTony\Observability\Context;

trait MessageTrait
{
    /** @var string */
    private $protocol = '1.1';

    /** @var string[][] Map of all registered headers, as original name => array of values */
    private $headers = [];

    /** @var string[] Map of lowercase header name => original name at registration */
    private $headerNames = [];

    private $body = '';

    private $bodySize = 0;

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function getBody(): string
    {
        if (empty($this->body)) {
            return '';
        }
        return $this->body;
    }

    public function getData(): array
    {
        if (is_array($this->body)) {
            return $this->body;
        }

        // 尝试将字符串转换为数组
        return json_decode($this->body, true);
    }

    public function getBodySize(): int
    {
        if (empty($this->bodySize)) {
            return 0;
        }
        return $this->bodySize;
    }

    public function getHeaders(): array
    {
        if (empty($this->headers)) {
            return [];
        }
        return $this->headers;
    }

    public function hasHeader($header): bool
    {
        if (empty($this->headerNames)) {
            return false;
        }
        return isset($this->headerNames[strtolower($header)]);
    }

    public function getHeader($header): array
    {
        if (empty($this->headers)) {
            return [];
        }
        $header = strtolower($header);

        if (!isset($this->headerNames[$header])) {
            return [];
        }

        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    public function getHeaderLine($header): string
    {
        return implode(', ', $this->getHeader($header));
    }

    public function setBody($body): void
    {
        if (is_array($body) || is_object($body)) {
            $this->body = json_encode($body, JSON_UNESCAPED_UNICODE);
        } else {
            $this->body = (string)$body;
        }

        $this->bodySize = strlen($this->body);
    }

    public function setHeader($key, $value): void
    {
        $header = (string)$key;
        $normalized = strtolower($key);

        // 确保每个 header 是一个数组
        if (isset($this->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];

            if (!is_array($this->headers[$header])) {
                $this->headers[$header] = [$this->headers[$header]]; // 将其转为数组
            }

            // 转换 $value 为数组，并去重
            $newValues = (array)$value;
            foreach ($newValues as $newValue) {
                if (!in_array($newValue, $this->headers[$header])) {
                    $this->headers[$header][] = $newValue; // 添加新值，避免重复
                }
            }
        } else {
            $this->headerNames[$normalized] = $header;
            $this->headers[$header] = (array)$value; // 确保值是数组类型
        }
    }

    public function setHeaders(array $headers): void
    {
        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }
    }

    /**
     * Get headers formatted for cURL.
     *
     * @return array
     */
    public function getCurlHeaders(): array
    {
        $curlHeaders = [];
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $curlHeaders[] = $name . ':' . $value;
            }
        }
        return $curlHeaders;
    }

    /**
     * Set headers from cURL formatted headers.
     *
     * @param array $curlHeaders
     */
    public function setCurlHeaders(array $curlHeaders): void
    {
        foreach ($curlHeaders as $header) {
            if (is_string($header) && strpos($header, ':') !== false) {
                list($name, $value) = explode(':', $header, 2);
                $this->setHeader($name, $value);
            } elseif (is_array($header)) {
                foreach ($header as $name => $value) {
                    $this->setHeader($name, $value);
                }
            }
        }
    }
}