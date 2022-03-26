<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Tests;

use Google\Cloud\Scheduler\V1beta1\CloudSchedulerClient;
use Illuminate\Console\Scheduling\Schedule;
use Orchestra\Testbench\TestCase;
use YlsIdeas\GoogleCloudSchedulerLaravel\CloudSchedulerServiceProvider;

class PublishesEventsToGoogleScheduleTest extends TestCase
{
    protected \Mockery\LegacyMockInterface|\Mockery\MockInterface|CloudSchedulerClient $googleClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->googleClient = $this->mock(CloudSchedulerClient::class);
    }

    public function testPublishesScheduledEventsToGoogleServices()
    {
        $scheduler = $this->app->make(Schedule::class);
        $event = $scheduler->command('inspire')
            ->when(function () {
                return true;
            })
            ->after(function () {
                return true;
            })
            ->everyFiveMinutes();

        $this->googleClient->shouldReceive('createJob')
            ->withArgs(function ($string, $job) {
                return true;
            })
            ->once();

        $this->artisan('google:cloud:scheduler', [
            '--no-clear' => true,
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $config = require __DIR__ . '/../config/scheduler.php';
        data_set($config, 'project_id', 'test');
        data_set($config, 'location', 'test');
        $app['config']->set('scheduler', $config);
    }

    protected function getPackageProviders($app): array
    {
        return [CloudSchedulerServiceProvider::class];
    }
}
