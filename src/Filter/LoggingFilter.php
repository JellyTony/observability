<?php

namespace JellyTony\Observability\Filter;

use Closure;
use JellyTony\Observability\Contracts\Filter;
use JellyTony\Observability\Contracts\Context;

class LoggingFilter implements Filter
{
    protected $timeFormat = 'Y-m-d H:i:s.u';
    protected $slowThreshold = 500; // 默认慢日志时间（毫秒）

    public function handle(Context $context, Closure $next, array $options = [])
    {
        $startTime = microtime(true);
        if (!empty($options['start_time'])) {
            $startTime = $options['start_time'];
        }

        try {
            $reply = $next($context, $options);
            $endTime = microtime(true);
            $this->terminate($context, $startTime, $endTime);
            return $reply;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            list($bizCode, $bizMsg) = convertExceptionToBizError($e);
            $context->setBizResult($bizCode, $bizMsg);
            $this->terminate($context, $startTime, $endTime);
            throw $e;
        }
    }

    public function terminate(Context $context, $startTime, $endTime)
    {
        if (!empty($options['end_time'])) {
            $endTime = $options['end_time'];
        }
        $service = 'unknown';
        if (!empty($options['service'])) {
            $service = $options['service'];
        }

        // 计算持续时间（毫秒）
        $duration = ($endTime - $startTime) * 1000;
        // 保留小数 2 位
        $latency = round($duration, 2);

        $fields = [
            'start' => date($this->timeFormat, $startTime),
            "kind" => "client",
            "component" => "http",
            "route" => $context->getRequest()->getUri()->getPath(),
            'latency' => $latency,
            "target_service" => getServiceName($service),
        ];

        if ($context->isError() || isMpDebug() || $latency > $this->slowThreshold) {
            $fields['biz_code'] = $context->getBizCode();
            $fields['biz_msg'] = $context->getBizCode();
            $fields['req_body'] = $context->getRequest()->getBody();
            $fields['req_header'] = $context->getRequest()->getHeaders();
            $fields['res_body'] = $context->getResponse()->getBody();
            $fields['res_header'] = $context->getResponse()->getHeaders();
        }

        if ($context->isError() && $latency > $this->slowThreshold) {
            \Log::error('http client slow', ['global_fields' => $fields]);
        } else if ($context->isError()) {
            \Log::error('http client', ['global_fields' => $fields]);
        } else if ($this->slowThreshold > 0 && $latency > $this->slowThreshold) {
            \Log::info('http client slow', ['global_fields' => $fields]);
        } else {
            \Log::debug('http client', ['global_fields' => $fields]);
        }
    }
}