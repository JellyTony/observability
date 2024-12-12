以下是 Markdown 原始格式，可以一键复制：

# JellyTony/Observability 使用说明

## 项目简介

`jellytony/observability` 是一个为 Laravel 框架提供的分布式可观测性工具。它支持 Zipkin 和 Jaeger 等追踪系统，通过集成追踪功能，可以帮助你在分布式应用中进行请求跟踪、性能监控、日志聚合等操作。

## 安装与配置

### 安装

通过 Composer 安装 `jellytony/observability`：

```bash
composer require jellytony/observability
```

### 配置

1. **服务提供者注册**

   在 Laravel 项目的 `config/app.php` 文件的 `providers` 数组中，添加 `TracingServiceProvider` 和 `LogServiceProvider`：

   ```php
   JellyTony\Observability\TracingServiceProvider::class,
   ```
  
   在 Lumen 项目中，在 `bootstrap/app.php` 文件中，添加 `TracingServiceProvider` 和 `LogServiceProvider`：

2. **配置文件**

   在 `config/observability.php` 中，你可以配置追踪系统（如 Zipkin）的相关设置。

   例如，配置 Zipkin 追踪：

    ```php
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
    ```

   你可以根据需要更改 Zipkin 的配置或选择不同的追踪系统（如 Jaeger）。

## 使用

### 启用请求跟踪

在 Laravel 中，追踪功能会自动处理 HTTP 请求和响应的追踪。你只需要确保 `TracingServiceProvider` 已正确注册，系统会自动捕获每个请求的信息。

### 启动日志注入 trace 信息
在 Laravel 中，你可以使用 `LogServiceProvider` 启用日志注入 trace 信息。

### 自定义中间件

该项目提供了一些自定义中间件用于处理请求跟踪：

- `RequestIdMiddleware`：生成唯一的请求 ID。
- `RequestLog`：记录请求日志。
- `TraceRequests`：执行请求的追踪逻辑。

这些中间件可以在 laravel 的 `app/Http/Kernel.php` 中启用：

```php
protected $middleware = [
    \JellyTony\Observability\Middleware\TraceRequests::class,
    \JellyTony\Observability\Middleware\RequestIdMiddleware::class,
    \JellyTony\Observability\Middleware\RequestLog::class,
];
```

在 Lumen 中，你可以在 `bootstrap/app.php` 中启用：
```php
    'request_id' => JellyTony\Observability\Middleware\RequestIdMiddleware::class,
    'zipkin' => JellyTony\Observability\Middleware\TraceRequests::class,
    'request_log' => JellyTony\Observability\Middleware\RequestLog::class,
```

然后在 `routes/web.php` 路由中添加路由，例如：

```php
<?php
$app->group(['middleware' => ['request_id','zipkin','request_id']], function (
    $app->get('/', function () {
        return $app->version();
    })
) )
```

### 使用追踪服务

你可以使用 `JellyTony\Observability\Facades\Trace` 进行手动追踪：

```php
use JellyTony\Observability\Facades\Trace;

Trace::startSpan('span_name');
// 执行代码
Trace::endSpan();
```

## 测试

该库包含了一些基础的单元测试，使用 PHPUnit 进行测试。

1. 安装 PHPUnit 依赖：

   ```bash
   composer install --dev
   ```

2. 运行测试：

   ```bash
   vendor/bin/phpunit
   ```

## 贡献

如果你想为此库贡献代码，欢迎提交 PR。你可以修改 `src` 目录中的代码或增加新的追踪驱动（例如支持 Jaeger）。确保修改或添加的功能有适当的测试覆盖。

## 支持的追踪系统

- **Zipkin**：默认支持，配置 Zipkin 服务器的端点即可开始使用。
- **Jaeger**（如果你计划支持）：可以通过扩展 `TracingDriverManager` 并实现 Jaeger 特定的追踪逻辑来添加支持。

## 许可证

MIT License.

你可以直接复制以上内容作为 Markdown 文件。