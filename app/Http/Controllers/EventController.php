<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $events = Event::all()->map(function ($event) {
            return $this->formatEvent($event);
        });
        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
        ]);

        // Convert to UTC before saving
        $validated['start_datetime'] = Carbon::parse($validated['start_datetime'])->utc();
        $validated['end_datetime'] = Carbon::parse($validated['end_datetime'])->utc();

        $event = Event::create($validated);

        return response()->json($this->formatEvent($event), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): JsonResponse
    {
        return response()->json($this->formatEvent($event));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string',
                'start_datetime' => 'required|date_format:Y-m-d H:i:s',
                'end_datetime' => 'required|date_format:Y-m-d H:i:s|after:start_datetime',
            ]);

            // Convert to UTC before saving
            $validated['start_datetime'] = Carbon::parse($validated['start_datetime'])->utc();
            $validated['end_datetime'] = Carbon::parse($validated['end_datetime'])->utc();

            $event->update($validated);

            return response()->json([
                'message' => 'Event updated successfully',
                'event' => $this->formatEvent($event->fresh())
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating event: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();
        return response()->json(null, 204);
    }

    /**
     * Format event dates consistently.
     */
    private function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'start_datetime' => Carbon::parse($event->start_datetime)->format('Y-m-d H:i:s'),
            'end_datetime' => Carbon::parse($event->end_datetime)->format('Y-m-d H:i:s'),
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ];
    }
}
