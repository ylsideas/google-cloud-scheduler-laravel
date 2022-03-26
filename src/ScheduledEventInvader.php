<?php

namespace YlsIdeas\GoogleCloudSchedulerLaravel;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Laravel\SerializableClosure\SerializableClosure;
use Spatie\Invade\Invader;

class ScheduledEventInvader
{
    public function invade(Event $event)
    {
        /** @var Invader|Event|CallbackEvent $invastion */
        $invastion = invade($event);

        $invastion->afterCallbacks = collect($invastion->afterCallbacks)
            ->map(fn (\Closure $closure) => new SerializableClosure($closure))
            ->all();

        $invastion->beforeCallbacks = collect($invastion->beforeCallbacks)
            ->map(fn (\Closure $closure) => new SerializableClosure($closure))
            ->all();

        $invastion->filters = collect($invastion->filters)
            ->map(fn ($filter) => $filter instanceof \Closure ? new SerializableClosure($filter) : $filter)
            ->all();

        $invastion->rejects = collect($invastion->rejects)
            ->map(fn ($reject) => $reject instanceof \Closure ? new SerializableClosure($reject) : $reject)
            ->all();

        if ($event instanceof CallbackEvent && $invastion->callback instanceof \Closure) {
            $invastion->callback = new SerializableClosure($invastion->callback);
        }

        return $event;
    }
}
