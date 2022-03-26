<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

/**
 * @mixin TestCase
 */
trait OverrideConsoleKernel
{
    /**
     * @param Application $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(\Illuminate\Contracts\Console\Kernel::class, function ($app) {
            return new Kernel($app, $app->make(Dispatcher::class));
        });
    }
}
