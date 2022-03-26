<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Commands;

use Google\Cloud\Scheduler\V1beta1\HttpMethod;
use Google\Cloud\Scheduler\V1beta1\HttpTarget;
use Google\Cloud\Scheduler\V1beta1\Job;
use Google\Cloud\Scheduler\V1beta1\OidcToken;
use Google\Cloud\Scheduler\V1beta1\RetryConfig;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\SerializableClosure\SerializableClosure;
use YlsIdeas\GoogleCloudSchedulerLaravel\GoogleCloudScheduler;
use YlsIdeas\GoogleCloudSchedulerLaravel\ScheduledEventInvader;

class ExportScheduleToGoogleCloud extends Command
{
    protected $signature = 'google:cloud:scheduler {--no-clear} {--ignore-target}';

    protected $description = 'Create scheduled jobs for Laravel within Google Cloud Scheduler.';

    public function handle(Schedule $schedule, ScheduledEventInvader $invader, GoogleCloudScheduler $cloudScheduler): int
    {
        if (! $this->option('no-clear')) {
            $this->call('google:cloud:scheduler:clear');
        }

        $target = (new HttpTarget())
            ->setHttpMethod(HttpMethod::POST)
            ->setUri($cloudScheduler->route());

        $target = $target
            ->setOidcToken(
                (new OidcToken())
                    ->setAudience($cloudScheduler->route())
                    ->setServiceAccountEmail(config('scheduler.drivers.ocid.service_account'))
            );

        collect($schedule->events())
            ->each(function (Event $event) use ($cloudScheduler, $target, $invader) {
                // Mutex cannot be serialized
                /** @phpstan-ignore-next-line  */
                $event->mutex = null;

                $event = $invader->invade($event);

                $cloudScheduler->client()->createJob(
                    $cloudScheduler->parent(),
                    (new Job())
                        ->setName($cloudScheduler->jobName($this->jobName($event)))
                        ->setRetryConfig(
                            (new RetryConfig())
                                ->setRetryCount(1)
                        )
                        ->setTimeZone(config('app.schedule_timezone', config('app.timezone')))
                        ->setSchedule($event->expression)
                        ->setDescription($event->description)
                        ->setHttpTarget(
                            (clone $target)
                                ->setBody(json_encode(['event' => serialize($event)]))
                        )
                );
            });

        return self::SUCCESS;
    }

    /**
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function jobName(Event $event): string
    {
        $expressionHash = substr(md5($event->expression), 5);

        if ($event instanceof CallbackEvent) {
            $callback = invade($event)->callback;
            /** @phpstan-ignore-next-line */
            if ($callback instanceof \Closure) {
                $string = md5(serialize(new SerializableClosure($callback)));

                return implode('-', ['Closure', $string, $expressionHash]);
            }

            return implode('-', ['Closure', md5(serialize($callback)), $expressionHash]);
        }

        return implode('-', ['Command', urlencode($event->command), $expressionHash]);
    }
}
