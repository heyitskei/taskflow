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
                ['role' => 'system', 'content' => 'You are a helpful assistant that can create calendar events. When users ask to schedule something, always use the create_calendar_event function.'],
                ['role' => 'user', 'content' => $request->input('prompt')],
            ];

            \Log::info('Sending request to OpenAI:', ['prompt' => $request->input('prompt')]);

            $result = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'tools' => [$tools]
            ]);

            \Log::info('Received response from OpenAI:', [
                'has_tool_calls' => isset($result->choices[0]->message->toolCalls),
                'message_content' => $result->choices[0]->message->content ?? null
            ]);

            // If no function call is made, return the normal response
            if (!isset($result->choices[0]->message->toolCalls)) {
                return response()->json($result);
            }

            $functionName = $result->choices[0]->message->toolCalls[0]->function->name;
            $functionParams = json_decode($result->choices[0]->message->toolCalls[0]->function->arguments);

            \Log::info('Function call details:', [
                'function' => $functionName,
                'params' => (array)$functionParams
            ]);

            $messages[] = $result->choices[0]->message->toArray();
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $result->choices[0]->message->toolCalls[0]->id,
                'content' => $this->$functionName($functionParams)
            ];

            $result2 = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'tools' => [$tools]
            ]);

            return response()->json($result2);
        } catch (Exception $e) {
            \Log::error('OpenAI request failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to process your request. ' . $e->getMessage()
            ], 500);
        }
    }

    private function create_calendar_event($params): string
    {
        try {
            \Log::info('Creating calendar event with params:', (array)$params);

            $startDateTime = Carbon::parse($params->date . ' ' . $params->start_time);
            $endDateTime = Carbon::parse($params->date . ' ' . $params->end_time);

            \Log::info('Parsed dates:', [
                'start' => $startDateTime->format('Y-m-d H:i:s'),
                'end' => $endDateTime->format('Y-m-d H:i:s')
            ]);

            if ($endDateTime <= $startDateTime) {
                \Log::warning('Invalid time range: end time is before or equal to start time');
                return "Error: End time must be after start time.";
            }

            $eventData = [
                'title' => $params->title,
                'start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
                'end_datetime' => $endDateTime->format('Y-m-d H:i:s')
            ];

            \Log::info('Creating event with data:', $eventData);
            $event = $this->eventService->createEvent($eventData);
            \Log::info('Event created successfully:', ['event_id' => $event['id'] ?? null]);

            return "Successfully created event: '{$params->title}' on {$params->date} from {$params->start_time} to {$params->end_time}";
        } catch (Exception $e) {
            \Log::error('Failed to create event:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "Failed to create event: " . $e->getMessage();
        }
    }

    public function get_weather($location)
    {
        return "its sunny, everything is fine in $location";
    }
}
