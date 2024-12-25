<?php

namespace JellyTony\Observability\Contracts;

use Zipkin\Span as Base;

interface Span extends Base
{
    /**
     * add tag
     * @param $key
     * @param $val
     * @return void
     */
    public function addTag($key,$val): void;
    /**
     * @param array $values
     * @return void
     */
    public function setTags(array $values): void;

    /**
     * @return bool
     */
    public function isRoot(): bool;
}
