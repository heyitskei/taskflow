<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIController extends Controller
{
    private EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function openai(Request $request): JsonResponse
    {
        try {
            $tools = [
                "type" => "function",
                "function" => [
                    "name" => "create_calendar_event",
                    "description" => "Create a new event in the calendar. Use this function when the user wants to schedule or create a calendar event.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "title" => [
                                "type" => "string",
                                "description" => "Title of the event"
                            ],
                            "date" => [
                                "type" => "string",
                                "description" => "Date of the event in YYYY-MM-DD format"
                            ],
                            "start_time" => [
                                "type" => "string",
                                "description" => "Start time in HH:mm format (24-hour)"
                            ],
                            "end_time" => [
                                "type" => "string",
                                "description" => "End time in HH:mm format (24-hour)"
                            ]
                        ],
                        "required" => ["title", "date", "start_time", "end_time"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ];

            $messages = [
                ['role' => 'system', 'content' => 'You are a helpful assistant that can create calendar events. When users ask to schedule something, always use the create_calendar_event function. When they mention "today", use today\'s actual date (' . date('Y-m-d') . ').'],
                ['role' => 'user', 'content' => $request->input('prompt')],
            ];

            $result = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'tools' => [$tools]
            ]);

            // If no function call is made, return the normal response
            if (!isset($result->choices[0]->message->toolCalls)) {
                return response()->json($result);
            }

            $functionName = $result->choices[0]->message->toolCalls[0]->function->name;
            $functionParams = json_decode($result->choices[0]->message->toolCalls[0]->function->arguments);

            \Log::debug('Processing function call', [
                'function' => $functionName,
                'params' => (array)$functionParams
            ]);

            $messages[] = $result->choices[0]->message->toArray();

            // Execute the function and get the result
            $functionResult = $this->$functionName($functionParams);

            // Add the function result to messages
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $result->choices[0]->message->toolCalls[0]->id,
                'content' => $functionResult['message']
            ];

            $result2 = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'tools' => [$tools]
            ]);

            // Include both the chat completion and event data in response
            return response()->json([
                'chat' => $result2,
                'event' => $functionResult['event'] ?? null
            ]);

        } catch (Exception $e) {
            \Log::error('OpenAI request failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => 'Failed to process your request. ' . $e->getMessage()
            ], 500);
        }
    }

    private function create_calendar_event($params): array
    {
        try {
            // Handle relative dates
            if ($params->date === date('Y-m-d')) {
                // If the date is today's date, ensure we use today's actual date
                $today = Carbon::today();
                $startDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $params->start_time);
                $endDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $params->end_time);
            } else {
                $startDateTime = Carbon::parse($params->date . ' ' . $params->start_time);
                $endDateTime = Carbon::parse($params->date . ' ' . $params->end_time);
            }

            if ($endDateTime <= $startDateTime) {
                \Log::warning('Invalid event time range', [
                    'start' => $startDateTime->format('Y-m-d H:i:s'),
                    'end' => $endDateTime->format('Y-m-d H:i:s')
                ]);
                return [
                    'message' => "Error: End time must be after start time.",
                    'event' => null
                ];
            }

            $eventData = [
                'title' => $params->title,
                'start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
                'end_datetime' => $endDateTime->format('Y-m-d H:i:s')
            ];

            \Log::debug('Creating event with data:', $eventData);

            $event = $this->eventService->createEvent($eventData);

            \Log::debug('Event created successfully', [
                'event_data' => $event,
                'event_id' => $event['id'] ?? null
            ]);

            return [
                'message' => "Successfully created event: '{$params->title}' on {$startDateTime->format('Y-m-d')} from {$params->start_time} to {$params->end_time}",
                'event' => $event
            ];
        } catch (Exception $e) {
            \Log::error('Event creation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'params' => (array)$params
            ]);
            return [
                'message' => "Failed to create event: " . $e->getMessage(),
                'event' => null
            ];
        }
    }

    public function get_weather($location)
    {
        return "its sunny, everything is fine in $location";
    }
}
