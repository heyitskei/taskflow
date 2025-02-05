<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that can create calendar events. When users ask to schedule something, create exactly one calendar event using the create_calendar_event function. When they mention "today", use today\'s actual date (' . date('Y-m-d') . '). After creating an event, simply acknowledge its creation - do not create additional events unless specifically requested by the user.'
                ],
                ['role' => 'user', 'content' => $request->input('prompt')],
            ];

            $createdEvents = [];
            $maxIterations = 3;
            $iterations = 0;

            do {
                $iterations++;

                $result = OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'tools' => [$tools]
                ]);

                $assistantMessage = $result->choices[0]->message;
                $messages[] = $assistantMessage->toArray();

                if (!isset($assistantMessage->toolCalls)) {
                    break;
                }

                foreach ($assistantMessage->toolCalls as $toolCall) {
                    $functionName = $toolCall->function->name;
                    $functionParams = json_decode($toolCall->function->arguments);

                    $functionResult = $this->$functionName($functionParams);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => $functionResult['message']
                    ];

                    if ($functionResult['event']) {
                        $createdEvents[] = $functionResult['event'];
                    }
                }

            } while ($iterations < $maxIterations && empty($createdEvents));

            $finalResult = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'tools' => [$tools]
            ]);

            return response()->json([
                'chat' => $finalResult,
                'events' => $createdEvents
            ]);

        } catch (Exception $e) {
            Log::error('OpenAI request failed', [
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
            if ($params->date === date('Y-m-d')) {
                $today = Carbon::today();
                $startDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $params->start_time);
                $endDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $params->end_time);
            } else {
                $startDateTime = Carbon::parse($params->date . ' ' . $params->start_time);
                $endDateTime = Carbon::parse($params->date . ' ' . $params->end_time);
            }

            if ($endDateTime <= $startDateTime) {
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

            $event = $this->eventService->createEvent($eventData);

            return [
                'message' => "Successfully created event: '{$params->title}' on {$startDateTime->format('Y-m-d')} from {$params->start_time} to {$params->end_time}",
                'event' => $event
            ];
        } catch (Exception $e) {
            Log::error('Event creation failed', [
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
}
