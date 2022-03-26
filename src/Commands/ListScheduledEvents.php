<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Commands;

use Google\Cloud\Scheduler\V1beta1\HttpMethod;
use Google\Cloud\Scheduler\V1beta1\HttpTarget;
use Google\Cloud\Scheduler\V1beta1\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;
use YlsIdeas\GoogleCloudSchedulerLaravel\GoogleCloudScheduler;

class ListScheduledEvents extends Command
{
    protected $signature = 'google:cloud:scheduler:list {--ignore-target}';

    protected $description = 'List Jobs in the Google Cloud Scheduler.';

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
            ->map(function (Job $job) {
                return [
                    'name' => $job->getName(),
                    'state' => Job\State::name($job->getState()),
                    'schedule' => $job->getSchedule(),
                    'time' => $job->getScheduleTime() ? Carbon::make($job->getScheduleTime()->toDateTime()) : null,
                    'last_attempt' => $job->getLastAttemptTime() ? Carbon::make($job->getLastAttemptTime()->toDateTime()) : null,
                ];
            })
            ->all();

        $this->table([
            'name', 'state', 'schedule', 'time', 'last attempt',
        ], $jobs);

        return self::SUCCESS;
    }
}
