<?php

return [

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

    'log_path' => env('LOG_PATH', storage_path("logs/lumen.log")),

    'driver' => env('TRACING_DRIVER', 'zipkin'),

    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    |
    | Use this to lookup your application (microservice) on a tracing dashboard.
    |
    */

    'service_name' => env('APP_NAME', 'microservice'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Configure settings for tracing HTTP requests. You can exclude certain paths
    | from tracing like '/horizon/api/*' (note that we can use wildcards), allow
    | headers to be logged or hide values for ones that have sensitive info. It
    | is also possible to specify content types for which you want to log
    | request and response bodies.
    |
    */

    'middleware' => [
        'debug' => env('middleware.debug', false),

        'request' => [
            'disable' => env('middleware.request.disable', false),
            'time_format' => env('middleware.request.time_format', 'Y-m-d H:i:s.u'),
            'latency_threshold' => env('middleware.request.latency_threshold', 3000),
            'dump_request_body' => env('middleware.request.dump_request_body', false),
            'dump_request_headers' => env('middleware.request.dump_request_headers', false),
            'dump_response_body' => env('middleware.request.dump_response_body', false),
            'dump_response_headers' => env('middleware.request.dump_response_headers', false),
        ],

        'trace' => [
            'disable' => env('middleware.trace.disable', false),
            'latency_threshold' => env('middleware.trace.latency_threshold', 3000),

            'excluded_paths' => [
                //
            ],

            'allowed_headers' => [
                '*'
            ],

            'sensitive_headers' => [
                //
            ],

            'sensitive_input' => [
                //
            ],

            'payload' => [
                'content_types' => [
                    'application/json',
                ],
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


];