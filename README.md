# JellyTony/Observability 使用说明

## 项目简介

`jellytony/observability` 是一个为 Laravel 框架提供的分布式可观测性工具。它支持 Zipkin 和 Jaeger
等追踪系统，通过集成追踪功能，可以帮助你在分布式应用中进行请求跟踪、性能监控、日志聚合等操作。

## 安装与配置

### 安装

通过 Composer 安装 `jellytony/observability`：

```bash
composer require jellytony/observability
```

### 配置

1. **服务提供者注册**

   在 Laravel 项目的 `config/app.php` 文件的 `providers` 数组中，添加 `ObservabilityServiceProvider` 和
   `LogServiceProvider`：

   ```php
   JellyTony\Observability\ObservabilityServiceProvider::class,
   JellyTony\Observability\LogServiceProvider::class,
   ```

   在 Lumen 项目中，在 `bootstrap/app.php` 文件中，添加 `ObservabilityServiceProvider` 和 `LogServiceProvider`：
   ```php
   $app->register(JellyTony\Observability\ObservabilityServiceProvider::class); // 注册 ObservabilityServiceProvider 提供器, 注意 顺序， 这个一定要在 LogServiceProvider 之前
   $app->register(JellyTony\Observability\LogServiceProvider::class); // 注册 LogServiceProvider 提供器
   ```

2. **配置文件**

   在 `config/observability.php` 中，你可以配置追踪系统（如 Zipkin）的相关设置。

   例如，配置 Zipkin 追踪：

    ```php
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
    ```

   你可以根据需要更改 Zipkin 的配置或选择不同的追踪系统（如 Jaeger）。

## 使用

### 启用请求跟踪

在 Laravel 中，追踪功能会自动处理 HTTP 请求和响应的追踪。你只需要确保 `ObservabilityServiceProvider` 已正确注册，系统会自动捕获每个请求的信息。

### 启动日志注入 trace 信息

在 Laravel 中，你可以使用 `LogServiceProvider` 启用日志注入 trace 信息。

### 自定义中间件

该项目提供了一些自定义中间件用于处理请求跟踪：

- `RequestID` ：生成唯一的请求 ID。
- `RequestLogging`：记录请求日志。
- `Tracing`：执行请求的追踪逻辑。

这些中间件可以在 laravel 的 `app/Http/Kernel.php` 中启用：

```php
protected $middleware = [
    \JellyTony\Observability\Middleware\RequestID::class,
    \JellyTony\Observability\Middleware\Tracing::class,
    \JellyTony\Observability\Middleware\RequestLogging::class,
];
```

在 Lumen 中，你可以在 `bootstrap/app.php` 中启用：

```php
    'request_id' => JellyTony\Observability\Middleware\RequestID::class,
    'tracing' => JellyTony\Observability\Middleware\Tracing::class,
    'logging' => JellyTony\Observability\Middleware\RequestLogging::class,
```

然后在 `routes/web.php` 路由中添加路由，例如：

```php
$app->group(['middleware' => ['request_id','tracing','logging']], function (
    $app->get('/', function () {
        return $app->version();
    })
))
```

### 使用追踪服务

你可以使用 `JellyTony\Observability\Facades\Trace` 进行手动追踪：

```php
use JellyTony\Observability\Facades\Trace;

$carrier = array_map(function ($header) {
   if ($header[0] == "") {
       return null;
   }
   return $header[0];
}, $request->headers->all());

// 提取请求头到 carrier ，做服务链路关联
$extractor = $this->tracer->getPropagation()->getExtractor(new Map());
$extractedContext = $extractor($carrier);
$spanName = sprintf("HTTP Server %s: %s", $request->method(), $request->path());

// 生成 span信息
$span = $this->tracer->startSpan($spanName, $extractedContext);
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