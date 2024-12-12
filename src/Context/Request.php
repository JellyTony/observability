<?php

namespace JellyTony\Observability\Context;

use JellyTony\Observability\Util\Uri;
use JellyTony\Observability\Contracts\UrlAbstract;

class Request
{
    use MessageTrait;

    /** @var string */
    private $method;

    /**
     * @var UrlAbstract
     */
    private $uri;


    public function __construct(
        string $method = "",
               $uri = "",
        array  $headers = [],
               $body = null,
        string $version = '1.1'
    )
    {
        if (!($uri instanceof UrlAbstract)) {
            $uri = new Uri($uri);
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->setBody($body);
        $this->setHeaders($headers);
        $this->protocol = $version;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): UrlAbstract
    {
        return $this->uri;
    }


    public function setMethod($method): Request
    {
        $this->method = $method;
        return $this;
    }

    public function setUri(UrlAbstract $uri): Request
    {
        $this->uri = $uri;
        return $this;
    }

    public function withUri(UrlAbstract $uri, $preserveHost = false): Request
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !isset($this->headerNames['host'])) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host == '') {
            return;
        }

        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':'.$port;
        }

        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }
        // Ensure Host is the first header.
        // See: https://datatracker.ietf.org/doc/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;
    }
}