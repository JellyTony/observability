<?php

namespace JellyTony\Observability\Contracts;

use JellyTony\Observability\Context\Request;
use JellyTony\Observability\Context\Response;

interface Context
{
    public function setBizCode($code);

    public function setBizMsg($msg);

    public function setBizResult($code, $msg);

    public function setBizContent($content);

    public function setRequest(Request $request): Context;

    public function setResponse(Response $response): Context;

    public function setMetadata($key, $value): Context;

    public function getMetadata($key = null): string;

    public function getRequest(): Request;

    public function getResponse(): Response;

    public function getBizCode(): int;

    public function getBizMsg(): string;

    public function isError() :bool;
}