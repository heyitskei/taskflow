<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
                [
                    "type" => "function",
                    "function" => [
                        "name" => "fetch_news",
                        "description" => "Fetch news for a user specified category",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "category" => [
                                    "type" => "string",
                                    "description" => "A category for which to fetch news for a given category",
                                ]
                            ],
                            "required" => ["category"],
                            "additionalProperties" => false
                        ],
                    ]
                ],
                [
                    "type" => "function",
                    "function" => [
                        "name" => "search_events",
                        "description" => "Search for existing events by title. Use this function first when you need to find an event to update.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "title" => [
                                    "type" => "string",
                                    "description" => "Title of the event to search for"
                                ]
                            ],
                            "required" => ["title"],
                            "additionalProperties" => false
                        ],
                    ]
                ],
                [
                    "type" => "function",
                    "function" => [
                        "name" => "create_calendar_event",
                        "description" => "Create a new event in the calendar. Use this function ONLY when the user wants to create a NEW calendar event. Don't make any assumptions, ask the user to clarify if any of the required fields are missing.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "title" => [
                                    "type" => "string",
                                    "description" => "Title of the event"
                                ],
                                "description" => [
                                    "type" => "string",
                                    "description" => "Optional description or details of the event. Can include notes, agenda, or any additional information."
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
                    ]
                ],
                [
                    "type" => "function",
                    "function" => [
                        "name" => "update_calendar_event",
                        "description" => "Update an existing event in the calendar. Must be called after finding the event using search_events.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "event_id" => [
                                    "type" => "integer",
                                    "description" => "ID of the existing event to update (obtained from search_events)"
                                ],
                                "title" => [
                                    "type" => "string",
                                    "description" => "New title of the event"
                                ],
                                "description" => [
                                    "type" => "string",
                                    "description" => "Optional description or details of the event. Can include notes, agenda, or any additional information."
                                ],
                                "date" => [
                                    "type" => "string",
                                    "description" => "New date of the event in YYYY-MM-DD format"
                                ],
                                "start_time" => [
                                    "type" => "string",
                                    "description" => "New start time in HH:mm format (24-hour)"
                                ],
                                "end_time" => [
                                    "type" => "string",
                                    "description" => "New end time in HH:mm format (24-hour)"
                                ]
                            ],
                            "required" => ["event_id", "title", "date", "start_time", "end_time"],
                            "additionalProperties" => false
                        ],
                    ]
                ]
            ];

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that can create and update calendar events.
                                  For NEW events: When users ask to schedule or create something new, use the create_calendar_event function.
                                  For EXISTING events: First use search_events to find the event, then use update_calendar_event with the found event\'s ID.
                                  When they mention "today", use today\'s actual date (' . date('Y-m-d') . ').
                                  You can add descriptions to events to include additional details, notes, or agenda items.
                                  After any action, acknowledge what was done and wait for further instructions.'
                ],
                ['role' => 'user', 'content' => $request->input('prompt')],
                // TODO: include all of the chat history AND + the user prompt
                // each request grows larger because it includes all chat history
            ];

            $createdEvents = [];
            $updatedEvents = [];
            $maxIterations = 3;
            $iterations = 0;

            do {
                $iterations++;

                $result = OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'tools' => $tools
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
                        if ($functionName === 'create_calendar_event') {
                            $createdEvents[] = $functionResult['event'];
                        } else if ($functionName === 'update_calendar_event') {
                            $updatedEvents[] = $functionResult['event'];
                        }
                    }
                }
            } while ($iterations < $maxIterations && (empty($createdEvents) && empty($updatedEvents)));

            //TODO: AI cannot exit the loop to actually prompt the user for clarification?
            // use diff. signal from openai that it's done

            dd($messages);

            $finalResult = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'tools' => $tools
            ]);

            return response()->json([
                'chat' => $finalResult,
                'events' => array_merge($createdEvents, $updatedEvents)
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
                'description' => $params->description ?? null,
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

    private function update_calendar_event($params): array
    {
        try {
            $event = Event::find($params->event_id);

            if (!$event) {
                return [
                    'message' => "Error: Event not found with ID {$params->event_id}",
                    'event' => null
                ];
            }

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
                'description' => $params->description ?? null,
                'start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
                'end_datetime' => $endDateTime->format('Y-m-d H:i:s')
            ];

            $updatedEvent = $this->eventService->updateEvent($event, $eventData);

            return [
                'message' => "Successfully updated event: '{$params->title}' on {$startDateTime->format('Y-m-d')} from {$params->start_time} to {$params->end_time}",
                'event' => $updatedEvent
            ];
        } catch (Exception $e) {
            Log::error('Event update failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'params' => (array)$params
            ]);

            return [
                'message' => "Failed to update event: " . $e->getMessage(),
                'event' => null
            ];
        }
    }

    private function search_events($params): array
    {
        $events = Event::where('title', 'like', '%' . $params->title . '%')
            ->get()
            ->map(function ($event) {
                $formatted = $this->eventService->formatEvent($event);
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => Carbon::parse($formatted['start_datetime'])->format('Y-m-d'),
                    'start_time' => Carbon::parse($formatted['start_datetime'])->format('H:i'),
                    'end_time' => Carbon::parse($formatted['end_datetime'])->format('H:i')
                ];
            });

        if ($events->isEmpty()) {
            return [
                'message' => "No events found with title containing '{$params->title}'",
                'event' => null
            ];
        }

        if ($events->count() === 1) {
            $event = $events->first();
            return [
                'message' => "Found event: '{$event['title']}' (ID: {$event['id']}) on {$event['date']} from {$event['start_time']} to {$event['end_time']}",
                'event' => $event
            ];
        }

        $eventsList = $events->map(function ($event) {
            return "- '{$event['title']}' (ID: {$event['id']}) on {$event['date']} from {$event['start_time']} to {$event['end_time']}";
        })->join("\n");

        return [
            'message' => "Found multiple matching events:\n{$eventsList}\nPlease specify which event you want to update by providing more details or the exact title.",
            'events' => $events->toArray(),
            'event' => null
        ];
    }

    public function fetch_news($params)
    {
        //  pass in category
        $category = $params->category;
        // call the endpoint with the specified category
        $refetchedNews = Http::get('https://api.thenewsapi.com/v1/news/top',
            [
                'api_token' => env('VITE_THE_NEWS_API'),
                'categories' => $category
            ]);
        dd($refetchedNews->body());
        // reload the component
    }
}
