<?php

namespace JellyTony\Observability\Context;

use JellyTony\Observability\Contracts\Context as RawContext;

class Context  implements RawContext
{
    use BusinessContextTrait;

    /**
     * @var Request
     */
    public $request = null;

    /**
     * @var Response
     */
    public $response = null;

    /**
     * @var array
     */
    public $metadata = [];

    public function __construct($request = null, $response = null)
    {
        if (empty($request)) {
            $request = new Request();
        }
        if (empty($response)) {
            $response = new Response();
        }
        $this->request = $request;
        $this->response = $response;
    }

    public function setRequest(Request $request): RawContext
    {
        $this->request = $request;
        return $this;
    }

    public function setResponse(Response $response): RawContext
    {
        $this->response = $response;
        return $this;
    }

    /**
     * set metadata.
     * @param $key
     * @param $value
     * @return $this
     */
    public function setMetadata($key, $value): RawContext
    {
        $this->metadata[$key] = $value;
        return $this;
    }


    /**
     * get metadata value.
     * @param $key
     * @return string
     */
    public function getMetadata($key = null): string
    {
        if (!empty($key) || !empty($this->metadata[$key])) {
            return "";
        }
        return $this->metadata[$key];
    }

    /**
     * get request.
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * get response.
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}