<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel;

use Google\Cloud\Scheduler\V1beta1\CloudSchedulerClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;

class GoogleCloudScheduler
{
    protected Repository $config;

    public function __construct(protected Container $container)
    {
        $this->config = $this->container->make(Repository::class);
    }

    public function driver(): array|null
    {
        if (! ($driver = config('scheduler.drivers', [])[config('scheduler.auth')] ?? false)) {
            return null;
        }

        return $driver;
    }

    public function parent(): string
    {
        return CloudSchedulerClient::locationName(
            $this->config->get('scheduler.project_id', ''),
            $this->config->get('scheduler.location', ''),
        );
    }

    public function client(): CloudSchedulerClient
    {
        return $this->container->make(CloudSchedulerClient::class);
    }

    public function route(): string
    {
        if (($domain = $this->config->get('scheduler.domain')) ?? false) {
            return 'https://' . $domain .
                $this->container->make(UrlGenerator::class)->route(
                    'google.schedulern',
                    [],
                    false
                );
        }

        return route('google.scheduler', [], true);
    }

    public function jobName(string $jobName): string
    {
        return CloudSchedulerClient::jobName(
            $this->config->get('scheduler.project_id', ''),
            $this->config->get('scheduler.location', ''),
            $jobName
        );
    }
}
