<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EventService
{
    public function getAllEvents(): Collection
    {
        return Event::all()->map(function ($event) {
            return $this->formatEvent($event);
        });
    }

    public function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_datetime' => Carbon::parse($event->start_datetime)->format('Y-m-d H:i:s'),
            'end_datetime' => Carbon::parse($event->end_datetime)->format('Y-m-d H:i:s'),
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ];
    }

    public function createEvent(array $data): array
    {
        $data['start_datetime'] = Carbon::parse($data['start_datetime'])->utc();
        $data['end_datetime'] = Carbon::parse($data['end_datetime'])->utc();

        $event = Event::create($data);
        return $this->formatEvent($event);
    }

    public function updateEvent(Event $event, array $data): array
    {
        $data['start_datetime'] = Carbon::parse($data['start_datetime'])->utc();
        $data['end_datetime'] = Carbon::parse($data['end_datetime'])->utc();

        $event->update($data);
        return $this->formatEvent($event->fresh());
    }

    public function deleteEvent(Event $event): void
    {
        $event->delete();
    }
}
