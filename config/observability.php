<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    |
    | Use this to lookup your application (microservice) on a tracing dashboard.
    |
    */

    'service_name' => env('APP_NAME', 'microservice'),

    'log_path' => env('LOG_PATH', storage_path("logs/lumen.log")),

    /*
    |--------------------------------------------------------------------------
    | Tracing Driver
    |--------------------------------------------------------------------------
    |
    | If you're a Jaeger user, we recommend you avail of zipkin driver with zipkin
    | compatible HTTP endpoint. Refer to Jaeger documentation for more details.
    |
    | Supported: "zipkin", "null"
    |
    */

    'driver' => env('TRACING_DRIVER', 'zipkin'),

    /*
    |--------------------------------------------------------------------------
    | Zipkin
    |--------------------------------------------------------------------------
    |
    | Configure settings for a zipkin driver like whether you want to use
    | 128-bit Trace IDs and what is the max value size for flushed span
    | tags in bytes. Values bigger than this amount will be discarded
    | but you will still see whether certain tag was reported or not.
    |
    */

    'zipkin' => [
        'endpoint' => env('zipkin.HttpReporterUrl', 'http://127.0.0.1:9411/api/v2/spans'),
        'options' => [
            '128bit' => env('zipkin.128bit', false),
            'max_tag_len' => env('zipkin.max_tag_len', 1048576),
            'request_timeout' => env("zipkin.HttpReporterTimeout", 5),
        ],
        'sampler_class' => \Zipkin\Samplers\BinarySampler::class,
        'percentage_sampler_rate' => env('zipkin.Rate', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'server' => [
            // Logging Middleware Configuration
            'logging' => [
                'disabled' => env('MIDDLEWARE_SERVER_LOGGING_DISABLED', false), // 是否禁用请求日志
                'time_format' => env('MIDDLEWARE_SERVER_LOGGING_TIME_FORMAT', 'Y-m-d H:i:s'), // 时间格式
                'latency_threshold' => env('MIDDLEWARE_SERVER_REQUEST_LATENCY_THRESHOLD', 3000), // 延迟阈值，默认为 3 秒
                'access_level' => env('MIDDLEWARE_SERVER_LOGGING_ACCESS_LEVEL', 'info'), // 请求日志级别，默认为 info，分为 info 和 debug 两种
                'excluded_paths' => env('MIDDLEWARE_SERVER_LOGGING_EXCLUDED_PATHS', []), // 不记录的请求路径
                'request_body' => env('MIDDLEWARE_SERVER_LOGGING_REQUEST_BODY', false), // 是否记录请求参数
                'request_body_max_size' => env('MIDDLEWARE_SERVER_LOGGING_REQUEST_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'request_headers' => env('MIDDLEWARE_SERVER_LOGGING_REQUEST_HEADERS', false), // 是否记录请求头
                'response_body' => env('MIDDLEWARE_SERVER_LOGGING_RESPONSE_BODY', false), // 是否记录返回数据
                'response_body_max_size' => env('MIDDLEWARE_SERVER_LOGGING_RESPONSE_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'response_headers' => env('MIDDLEWARE_SERVER_LOGGING_RESPONSE_HEADERS', false), // 是否记录返回头
                'allowed_headers' => env('MIDDLEWARE_SERVER_LOGGING_ALLOWED_HEADERS', ['Content-Type', 'Authorization']), // 允许的头部
                'sensitive_headers' => env('MIDDLEWARE_SERVER_LOGGING_SENSITIVE_HEADERS', ['Authorization', 'Cookie']), // 敏感的头部
                'sensitive_input' => env('MIDDLEWARE_SERVER_LOGGING_SENSITIVE_INPUT', ['password']), // 敏感的输入
            ],

            // Trace Middleware Configuration
            'trace' => [
                'disabled' => env('MIDDLEWARE_SERVER_TRACE_DISABLED', false), // 是否禁用
                'latency_threshold' => env('MIDDLEWARE_SERVER_TRACE_LATENCY_THRESHOLD', 3000),  // 延迟阈值, 默认为 3 秒
                'excluded_paths' => env('MIDDLEWARE_SERVER_TRACE_EXCLUDED_PATHS', []), // 不记录的请求路径
                'request_body' => env('MIDDLEWARE_SERVER_TRACE_REQUEST_BODY', false), // 是否记录请求参数
                'request_body_max_size' => env('MIDDLEWARE_SERVER_TRACE_REQUEST_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'request_headers' => env('MIDDLEWARE_SERVER_TRACE_REQUEST_HEADERS', false), // 是否记录请求头
                'response_body' => env('MIDDLEWARE_SERVER_TRACE_RESPONSE_BODY', false), // 是否记录返回数据
                'response_body_max_size' => env('MIDDLEWARE_SERVER_TRACE_RESPONSE_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'response_headers' => env('MIDDLEWARE_SERVER_TRACE_RESPONSE_HEADERS', false), // 是否记录返回头
                'allowed_headers' => env('MIDDLEWARE_SERVER_TRACE_ALLOWED_HEADERS', ['Content-Type', 'Authorization']), // 允许的头部
                'sensitive_headers' => env('MIDDLEWARE_SERVER_TRACE_SENSITIVE_HEADERS', ['Authorization', 'Cookie']), // 敏感的头部
                'sensitive_input' => env('MIDDLEWARE_SERVER_TRACE_SENSITIVE_INPUT', ['password']), // 敏感的输入
            ],
        ],

        'client' => [
            // Logging Middleware Configuration
            'logging' => [
                'disabled' => env('MIDDLEWARE_CLIENT_LOGGING_DISABLED', false), // 是否禁用请求日志
                'time_format' => env('MIDDLEWARE_CLIENT_LOGGING_TIME_FORMAT', 'Y-m-d H:i:s'), // 时间格式
                'latency_threshold' => env('MIDDLEWARE_CLIENT_REQUEST_LATENCY_THRESHOLD', 3000), // 延迟阈值，默认为 3 秒
                'access_level' => env('MIDDLEWARE_CLIENT_LOGGING_ACCESS_LEVEL', 'info'), // 请求日志级别，默认为 info，分为 info 和 debug 两种
                'excluded_paths' => env('MIDDLEWARE_CLIENT_LOGGING_EXCLUDED_PATHS', []), // 不记录的请求路径
                'request_body' => env('MIDDLEWARE_CLIENT_LOGGING_REQUEST_BODY', false), // 是否记录请求参数
                'request_body_max_size' => env('MIDDLEWARE_CLIENT_LOGGING_REQUEST_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'request_headers' => env('MIDDLEWARE_CLIENT_LOGGING_REQUEST_HEADERS', false), // 是否记录请求头
                'response_body' => env('MIDDLEWARE_CLIENT_LOGGING_RESPONSE_BODY', false), // 是否记录返回数据
                'response_body_max_size' => env('MIDDLEWARE_CLIENT_LOGGING_RESPONSE_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'response_headers' => env('MIDDLEWARE_CLIENT_LOGGING_RESPONSE_HEADERS', false), // 是否记录返回头
                'allowed_headers' => env('MIDDLEWARE_CLIENT_LOGGING_ALLOWED_HEADERS', ['Content-Type', 'Authorization']), // 允许的头部
                'sensitive_headers' => env('MIDDLEWARE_CLIENT_LOGGING_SENSITIVE_HEADERS', ['Authorization', 'Cookie']), // 敏感的头部
                'sensitive_input' => env('MIDDLEWARE_CLIENT_LOGGING_SENSITIVE_INPUT', ['password']), // 敏感的输入
            ],

            // Trace Middleware Configuration
            'trace' => [
                'disabled' => env('MIDDLEWARE_CLIENT_TRACE_DISABLED', false), // 是否禁用
                'latency_threshold' => env('MIDDLEWARE_CLIENT_TRACE_LATENCY_THRESHOLD', 3000),  // 延迟阈值, 默认为 3 秒
                'excluded_paths' => env('MIDDLEWARE_CLIENT_TRACE_EXCLUDED_PATHS', []), // 不记录的请求路径
                'request_body' => env('MIDDLEWARE_CLIENT_TRACE_REQUEST_BODY', false), // 是否记录请求参数
                'request_body_max_size' => env('MIDDLEWARE_CLIENT_TRACE_REQUEST_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'request_headers' => env('MIDDLEWARE_CLIENT_TRACE_REQUEST_HEADERS', false), // 是否记录请求头
                'response_body' => env('MIDDLEWARE_CLIENT_TRACE_RESPONSE_BODY', false), // 是否记录返回数据
                'response_body_max_size' => env('MIDDLEWARE_CLIENT_TRACE_RESPONSE_BODY_MAX_SIZE', 102400), // 请求参数最大值，默认为 2kb
                'response_headers' => env('MIDDLEWARE_CLIENT_TRACE_RESPONSE_HEADERS', false), // 是否记录返回头
                'allowed_headers' => env('MIDDLEWARE_CLIENT_TRACE_ALLOWED_HEADERS', ['Content-Type', 'Authorization']), // 允许的头部
                'sensitive_headers' => env('MIDDLEWARE_CLIENT_TRACE_SENSITIVE_HEADERS', ['Authorization', 'Cookie']), // 敏感的头部
                'sensitive_input' => env('MIDDLEWARE_CLIENT_TRACE_SENSITIVE_INPUT', ['password']), // 敏感的输入
            ],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    |
    | Whether you want to automatically tag span with error=true
    | to denote the operation represented by the Span has failed
    | when error message was logged
    |
    */

    'errors' => true,


];