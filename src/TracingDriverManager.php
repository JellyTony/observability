<?php

namespace JellyTony\Observability;

use JellyTony\Observability\Contracts\Tracer;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use JellyTony\Observability\Drivers\Null\NullTracer;
use JellyTony\Observability\Drivers\Zipkin\ZipkinTracer;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Samplers\PercentageSampler;

class TracingDriverManager extends Manager
{
    /**
     * @var Repository
     */
    protected $config;

    /**
     * EngineManager constructor.
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->config = $app->make('config');
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        if (is_null($this->config->get('observability.driver'))) {
            return 'null';
        }

        return $this->config->get('observability.driver');
    }

    /**
     * Create an instance of Zipkin tracing engine
     *
     * @return ZipkinTracer|Tracer
     * @throws InvalidArgumentException
     */
    public function createZipkinDriver()
    {
        $tracer = new ZipkinTracer(
            $this->config->get('observability.service_name'),
            $this->config->get('observability.zipkin.endpoint'),
            $this->config->get('observability.zipkin.options.128bit'),
            $this->config->get('observability.zipkin.options.request_timeout', 5),
            $this->config->get('observability.zipkin.reporter_type', "http"),
            null,
            $this->getZipkinSampler()
        );

        ZipkinTracer::setMaxTagLen(
            $this->config->get('observability.zipkin.options.max_tag_len', ZipkinTracer::getMaxTagLen())
        );

        return $tracer->init();
    }

    public function createNullDriver()
    {
        return new NullTracer();
    }

    /**
     * @return BinarySampler|PercentageSampler
     * @throws InvalidArgumentException
     */
    protected function getZipkinSampler()
    {
        $samplerClassName = $this->config->get('observability.zipkin.sampler_class');
        if (!class_exists($samplerClassName)) {
            throw new InvalidArgumentException(
                \sprintf('Invalid sampler class. Expected `BinarySampler` or `PercentageSampler`, got %f', $samplerClassName)
            );
        }

        switch ($samplerClassName) {
            case BinarySampler::class:
                $sampler = BinarySampler::createAsAlwaysSample();
                break;
            case PercentageSampler::class:
                $sampler = PercentageSampler::create($this->config->get('observability.zipkin.percentage_sampler_rate'));
                break;
        }

        return $sampler;
    }
}
