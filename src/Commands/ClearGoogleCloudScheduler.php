<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Commands;

use Google\Cloud\Scheduler\V1beta1\HttpMethod;
use Google\Cloud\Scheduler\V1beta1\HttpTarget;
use Google\Cloud\Scheduler\V1beta1\Job;
use Illuminate\Console\Command;
use Illuminate\Support\LazyCollection;
use YlsIdeas\GoogleCloudSchedulerLaravel\GoogleCloudScheduler;

class ClearGoogleCloudScheduler extends Command
{
    protected $signature = 'google:cloud:scheduler:clear {--ignore-target}';

    protected $description = 'Clear Jobs in the Google Cloud Scheduler.';

    public function handle(GoogleCloudScheduler $cloudScheduler): int
    {
        $client = $cloudScheduler->client();
        $iterator = $client->listJobs($cloudScheduler->parent())->getIterator();

        $target = (new HttpTarget())
            ->setHttpMethod(HttpMethod::POST)
            ->setUri($cloudScheduler->route());

        $jobs = LazyCollection::make(function () use ($iterator) {
            while ($item = $iterator->current()) {
                yield $item;
                $iterator->next();
            }
        })
            ->when(! $this->option('ignore-target'), function (LazyCollection $collection) use ($target) {
                return $collection
                    ->filter(function (Job $job) use ($target) {
                        return $job->getHttpTarget()->getUri() === $target->getUri();
                    });
            })
            ->map(function (Job $job) use ($client) {
                $client->deleteJob($job->getName());

                return [$job->getName()];
            })
            ->all();

        $this->line(sprintf('%d jobs removed', count($jobs)));
        $this->table(['job'], $jobs);

        return self::SUCCESS;
    }
}
