<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create event with factory', function () {
    $event = Event::factory()->create();

    expect($event)
        ->toBeInstanceOf(Event::class)
        ->title->not->toBeEmpty()
        ->start_datetime->not->toBeNull()
        ->end_datetime->not->toBeNull();
});

test('event dates are stored in UTC', function () {
    $event = Event::create([
        'title' => 'Test Event',
        'start_datetime' => '2024-01-01 09:00:00',
        'end_datetime' => '2024-01-01 10:00:00',
    ]);

    $freshEvent = $event->fresh();
    expect($freshEvent->start_datetime->format('Y-m-d H:i:s'))->toBe('2024-01-01 09:00:00')
        ->and($freshEvent->end_datetime->format('Y-m-d H:i:s'))->toBe('2024-01-01 10:00:00');
});

test('event has required attributes', function () {
    $event = new Event();

    expect($event->getFillable())
        ->toContain('title')
        ->toContain('start_datetime')
        ->toContain('end_datetime');
});
