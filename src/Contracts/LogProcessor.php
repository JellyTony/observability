<?php

namespace JellyTony\Observability\Contracts;

interface LogProcessor
{
    public function process(LogRecord $record): LogRecord;
}