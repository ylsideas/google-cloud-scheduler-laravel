<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use YlsIdeas\GoogleCloudSchedulerLaravel\CloudSchedulerServiceProvider;
use YlsIdeas\GoogleCloudSchedulerLaravel\ScheduledEventInvader;

class ProcessEventTest extends TestCase
{
    public function testRunningEvent()
    {
        Carbon::setTestNow(Carbon::create(2020, 9, 25, 0, 0));

        $schedule = $this->app->make(Schedule::class);

        $event = $schedule->exec('inspire')
            ->when(function () {
                return true;
            })
            ->everyFiveMinutes();

        $event = (new ScheduledEventInvader())->invade($event);

        $event->mutex = null;

        $this->post(route('google.scheduler'), ['event' => serialize($event)])
            ->assertOk();
    }

    public function testOnlyRunsEventsWhichAreDue()
    {
        Carbon::setTestNow(Carbon::create(2020, 9, 25, 0, 1));

        $schedule = $this->app->make(Schedule::class);

        $event = $schedule->exec('inspire')
            ->when(function () {
                return false;
            })
            ->everyFiveMinutes();

        $event->mutex = null;

        $event = (new ScheduledEventInvader())->invade($event);

        $this->post(route('google.scheduler'), ['event' => serialize($event)])
            ->assertStatus(Response::HTTP_BAD_REQUEST);
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
        $app['config']->set('scheduler.project_id', 'test_project');
        $app['config']->set('scheduler.location', 'test_location');
    }

    protected function getPackageProviders($app)
    {
        return [CloudSchedulerServiceProvider::class];
    }
}
