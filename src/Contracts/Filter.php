<?php

namespace JellyTony\Observability\Contracts;

use Closure;


interface Filter
{
    public function handle(Context $context,  Closure $next, array $options = []);
}