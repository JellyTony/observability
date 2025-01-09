<?php

namespace JellyTony\Observability\Tests;

use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Util\Uri;
use JellyTony\Observability\Util\UriNull;
use PHPUnit\Framework\TestCase;

class RequestTest  extends TestCase {
    public function testRequestInitializationWithValidUri()
    {
        $request = new Request('GET', 'http://example.com', ['Content-Type' => 'application/json'], 'body content');

        $this->assertEquals('GET', $request->getMethod());
        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals('body content', $request->getBody());
        $this->assertEquals('http://example.com', (string)$request->getUri());
    }

    public function testParseWithIPv6Uri()
    {
        $uri = 'http://[2001:db8::1]:8080/path';
        $request = new Request('GET', $uri);

        // 检查是否正确解析了 URI
        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals($uri, (string)$request->getUri());
    }


    public function testSetMethod()
    {
        $request = new Request();
        $request->setMethod('PUT');

        $this->assertEquals('PUT', $request->getMethod());
    }

    public function testSetUri()
    {
        $uri = new Uri('http://example.com');
        $request = new Request();
        $request->setUri($uri);

        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals('http://example.com', (string)$request->getUri());
    }

    public function testWithUriPreservesHost()
    {
        $uri1 = new Uri('http://example.com');
        $uri2 = new Uri('http://new-example.com');
        $request = new Request('GET', (string)$uri1);

        $newRequest = $request->withUri($uri2, true);

        $this->assertNotSame($request, $newRequest);
        $this->assertEquals('http://new-example.com', (string)$newRequest->getUri());
    }

    public function testUpdateHostFromUri()
    {
        $uri = new Uri('http://example.com:8080');
        $request = new Request('GET', (string)$uri);
        $requestReflection = new \ReflectionClass(Request::class);

        // 获取 updateHostFromUri 方法
        $method = $requestReflection->getMethod('updateHostFromUri');
        $method->setAccessible(true);

        $method->invoke($request);

        $this->assertArrayHasKey('Host', $request->getHeaders());
        $this->assertEquals(['example.com:8080'], $request->getHeaders()['Host']);
    }
}