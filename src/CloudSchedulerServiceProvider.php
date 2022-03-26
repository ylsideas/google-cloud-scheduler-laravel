<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel;

use Google\Cloud\Scheduler\V1beta1\CloudSchedulerClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TradeCoverExchange\GoogleJwtVerifier\Laravel\AuthenticateByOidc;
use YlsIdeas\GoogleCloudSchedulerLaravel\Controllers\ScheduleEventController;
use YlsIdeas\GoogleCloudSchedulerLaravel\Facades\GoogleCloudScheduler as CloudScheduler;

class CloudSchedulerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->scoped(CloudScheduler::class);

        $this->app->scoped(CloudSchedulerClient::class, function () {
            return new CloudSchedulerClient(config('scheduler.options', []));
        });
    }

    public function boot()
    {
        $middlewares = [];

        $driver = CloudScheduler::driver();

        if (($driver['type'] ?? false) === 'oidc') {
            $middlewares = [AuthenticateByOidc::middleware($driver['service_account'] ?? '')];
        }
        if (($driver['type'] ?? false) === 'appengine') {
            $middlewares = [AuthenticateByOidc::middleware($driver['service_account'] ?? '')];
        }

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../config/scheduler.php' => config_path('scheduler.php'),
                ],
                'google-cloud-scheduler'
            );

            $this->commands([
                Commands\ExportScheduleToGoogleCloud::class,
                Commands\ClearGoogleCloudScheduler::class,
                Commands\ListScheduledEvents::class,
            ]);
        }

        Route::post('/_googleScheduler', ScheduleEventController::class)
            ->middleware($middlewares)
            ->name('google.scheduler');
    }
}
