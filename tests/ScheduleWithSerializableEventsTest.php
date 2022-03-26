<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Tests;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Orchestra\Testbench\TestCase;
use YlsIdeas\GoogleCloudSchedulerLaravel\CloudSchedulerServiceProvider;
use YlsIdeas\GoogleCloudSchedulerLaravel\ScheduledEventInvader;

class ScheduleWithSerializableEventsTest extends TestCase
{
    public function testSerialisingEvents()
    {
        $scheduler = $this->app->make(Schedule::class);
        $scheduler->command('inspire')
            ->when(function () {
                return true;
            })
            ->after(function () {
                return true;
            })
            ->everyFiveMinutes();

        /** @var Event $event */
        $event = collect($scheduler->events())->first();

        $event->mutex = null;

        $event = (new ScheduledEventInvader())->invade($event);

        $event = unserialize(serialize($event));
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testSerialisingEventsWithCallables()
    {
        /** @var Schedule $scheduler */
        $scheduler = $this->app->make(Schedule::class);
        $scheduler->call(function () {
            return 'test';
        })
            ->when(function () {
                return true;
            })
            ->after(function () {
                return true;
            })
            ->everyFiveMinutes();

        /** @var Event $event */
        $event = collect($scheduler->events())->first();

        $event->mutex = null;

        $event = (new ScheduledEventInvader())->invade($event);

        $event = unserialize(serialize($event));
        $this->assertInstanceOf(Event::class, $event);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('scheduler', require __DIR__ . '/../config/scheduler.php');
    }

    protected function getPackageProviders($app): array
    {
        return [CloudSchedulerServiceProvider::class];
    }
}
