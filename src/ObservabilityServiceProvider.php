<?php

namespace JellyTony\Observability;

use Illuminate\Support\ServiceProvider;
use JellyTony\Observability\Contracts\Tracer;
use JellyTony\Observability\Facades\Trace;
use JellyTony\Observability\Metadata\InMemoryMetadataStorage;
use JellyTony\Observability\Metadata\Metadata;

class ObservabilityServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole() && function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/observability.php' => config_path('observability.php'),
            ]);
        }

        if (method_exists($this->app, 'terminating')) {
            $this->app->terminating(function () {
                if(!empty(Trace::getRootSpan())) {
                    Trace::getRootSpan()->finish();
                }
                Trace::flush();
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/observability.php', 'observability');

        // 注册 TracingDriverManager 为单例
        $this->app->singleton(TracingDriverManager::class, function ($app) {
            return new TracingDriverManager($app);
        });

        // 注册 Tracer 类作为 TracingDriverManager 的依赖单例
        $this->app->singleton(Tracer::class, function ($app) {
            return $app->make(TracingDriverManager::class)->driver($this->app['config']['observability.driver']);
        });

        $this->app->alias(Tracer::class, 'tracer');


        // 注册 InMemoryMetadataStorage 为单例
        $this->app->singleton(InMemoryMetadataStorage::class, function () {
            return new InMemoryMetadataStorage();  // 或者你可以传入其他类型的存储类
        });

        // 注册 Metadata 为单例
        $this->app->singleton(Metadata::class, function ($app) {
            // 通过依赖注入将 InMemoryMetadataStorage 注入 Metadata 类
            return new Metadata($app->make(InMemoryMetadataStorage::class));
        });

        $this->app->singleton(Metadata::class, function(){
            return new Metadata();
        });

        $this->app->alias(Metadata::class, 'metadata');
    }

    public function provides()
    {
        return [
            TracingDriverManager::class,
            Tracer::class,
            InMemoryMetadataStorage::class,
            Metadata::class,
        ];
    }
}
