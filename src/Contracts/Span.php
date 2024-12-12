<?php

namespace JellyTony\Observability\Contracts;

use Zipkin\Span as Base;

interface Span extends Base
{
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
