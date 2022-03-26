<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Facades;

use Google\Cloud\Scheduler\V1beta1\CloudSchedulerClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array driver()
 * @method static CloudSchedulerClient client()
 * @method static string parent()
 */
class GoogleCloudScheduler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \YlsIdeas\GoogleCloudSchedulerLaravel\GoogleCloudScheduler::class;
    }
}
