<?php

namespace JellyTony\Observability\Context;

class Response
{
    use MessageTrait;

    private const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /** @var int */
    private $statusCode;

    private $reasonPhrase;

    public function __construct(
        int     $status = 200,
        array   $headers = [],
                $body = null,
        ?string $reason = null,
        string  $version = '1.1'
    )
    {
        $this->statusCode = $status;

        $this->setHeaders($headers);
        $this->setBody($body);
        if ($reason == '' && isset(self::PHRASES[$this->statusCode])) {
            $this->reasonPhrase = self::PHRASES[$this->statusCode];
        } else {
            $this->reasonPhrase = (string)$reason;
        }
        $this->protocol = $version;

        return $this;
    }

    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }


    /**
     * 从 curl_getinfo 获取响应信息
     * @param array $curlInfo
     * @param $body
     */
    public function fromCurlInfo(array $curlInfo, $body)
    {
        // 取得状态码
        $statusCode = 200;
        if (!empty($curlInfo['http_code'])) {
            $statusCode = $curlInfo['http_code'];
            $this->setStatusCode($statusCode);
        }

        // 取得响应头
        if (!empty($curlInfo['request_header'])) {
            $headers = $this->parseHeaders($curlInfo['request_header']);
            $this->setHeaders($headers);
        }

        // 设置 body
        if (!empty($body)) {
            $this->setBody($body);
        }
    }

    // 解析 headers
    private function parseHeaders(string $headerStr): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerStr);
        foreach ($lines as $line) {
            if (empty($line)) continue;
            list($key, $value) = explode(':', $line, 2);
            $headers[$key] = $value;
        }
        return $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = ''): void
    {
        $code = (int)$code;
        $new = clone $this;
        $new->statusCode = $code;
        if ($reasonPhrase == '' && isset(self::PHRASES[$new->statusCode])) {
            $reasonPhrase = self::PHRASES[$new->statusCode];
        }
        $new->reasonPhrase = (string)$reasonPhrase;
    }
}