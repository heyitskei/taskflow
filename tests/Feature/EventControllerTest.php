<?php

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set a fixed time for consistent testing
    Carbon::setTestNow('2024-01-01 12:00:00');
});

test('can list all events', function () {
    // Arrange
    $events = Event::factory()->count(3)->create();

    // Act
    $response = $this->getJson('/events');

    // Assert
    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => ['id', 'title', 'start_datetime', 'end_datetime']
        ]);
});

test('can create an event', function () {
    // Arrange
    $eventData = [
        'title' => 'Test Event',
        'start_datetime' => '2024-01-01 09:00:00',
        'end_datetime' => '2024-01-01 10:00:00',
    ];

    // Act
    $response = $this->postJson('/events', $eventData);

    // Assert
    $response->assertStatus(201)
        ->assertJson([
            'title' => 'Test Event',
            'start_datetime' => '2024-01-01 09:00:00',
            'end_datetime' => '2024-01-01 10:00:00',
        ]);

    $this->assertDatabaseHas('events', [
        'title' => 'Test Event'
    ]);
});

test('cannot create event with end time before start time', function () {
    // Arrange
    $eventData = [
        'title' => 'Invalid Event',
        'start_datetime' => '2024-01-01 10:00:00',
        'end_datetime' => '2024-01-01 09:00:00',
    ];

    // Act
    $response = $this->postJson('/events', $eventData);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['end_datetime']);
});

test('can update an event', function () {
    // Arrange
    $event = Event::factory()->create();
    $updateData = [
        'title' => 'Updated Event',
        'start_datetime' => '2024-01-01 11:00:00',
        'end_datetime' => '2024-01-01 12:00:00',
    ];

    // Act
    $response = $this->putJson("/events/{$event->id}", $updateData);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Event updated successfully',
            'event' => [
                'title' => 'Updated Event',
                'start_datetime' => '2024-01-01 11:00:00',
                'end_datetime' => '2024-01-01 12:00:00',
            ]
        ]);
});

test('can delete an event', function () {
    // Arrange
    $event = Event::factory()->create();

    // Act
    $response = $this->deleteJson("/events/{$event->id}");

    // Assert
    $response->assertStatus(204);
    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('validates required fields when creating event', function () {
    // Act
    $response = $this->postJson('/events', []);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'start_datetime', 'end_datetime']);
}); 