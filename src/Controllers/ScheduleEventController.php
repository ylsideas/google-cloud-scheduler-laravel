<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel\Controllers;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ScheduleEventController
{
    public function __invoke(
        Request $request,
        Application $application,
        ExceptionHandler $reporter,
        Schedule $schedule
    ): Response {
        $event = unserialize($request->input('event'));

        if (! $event instanceof Event) {
            throw new \RuntimeException('Expecting a schedule event object');
        }

        $event->mutex = invade($schedule)->eventMutex;

        if (! $event->filtersPass($application)) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }

        try {
            $event->run($application);
        } catch (\Exception $exception) {
            $reporter->report($exception);

            return response($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->noContent(Response::HTTP_OK);
    }
}
