<?php

namespace JellyTony\Observability;

use Illuminate\Support\ServiceProvider;
use JellyTony\Observability\Contracts\Tracer;
use JellyTony\Observability\Facades\Trace;

class TracingServiceProvider extends ServiceProvider
{
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

        $this->app->singleton(TracingDriverManager::class, function ($app) {
            return new TracingDriverManager($app);
        });

        $this->app->singleton(Tracer::class, function ($app) {
            return $app->make(TracingDriverManager::class)->driver($this->app['config']['observability.driver']);
        });
    }
}
