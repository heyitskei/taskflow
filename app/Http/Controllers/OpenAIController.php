<?php

namespace App\Http\Controllers;

use App\Models\Event;
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
//                [
//                    "type" => "function",
//                    "function" => [
//                        "name" => "fetch_news",
//                        "description" => "Fetch news for a user specified category",
//                        "parameters" => [
//                            "type" => "object",
//                            "properties" => [
//                                "category" => [
//                                    "type" => "string",
//                                    "description" => "A category for which to fetch news for a given category",
//                                ]
//                            ],
//                            "required" => ["category"],
//                            "additionalProperties" => false
//                        ],
//                    ]
//                ],
                [
                    "type" => "function",
                    "function" => [
                        "name" => "search_events",
                        "description" => "Search for existing events using any combination of criteria. Use this function first when you need to find an event to update. If multiple events are found, use additional criteria to narrow down the search.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "title" => [
                                    "type" => "string",
                                    "description" => "Full or partial title of the event"
                                ],
                                "description" => [
                                    "type" => "string",
                                    "description" => "Full or partial description text to search for"
                                ],
                                "date" => [
                                    "type" => "string",
                                    "description" => "Date of the event in YYYY-MM-DD format"
                                ],
                                "start_time" => [
                                    "type" => "string",
                                    "description" => "Start time of the event in HH:mm format (24-hour)"
                                ],
                                "end_time" => [
                                    "type" => "string",
                                    "description" => "End time of the event in HH:mm format (24-hour)"
                                ],
                            ],
                            "required" => [],
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
                    'content' => 'You are a helpful AI assistant that can engage in general conversation and manage calendar events.

                                  CONVERSATION GUIDELINES:
                                  - Maintain a natural, friendly conversation flow
                                  - Respond appropriately to greetings, questions, and casual conversation
                                  - Stay focused on the current context without making assumptions
                                  - Ask clarifying questions when needed

                                  CALENDAR FUNCTIONALITY:
                                  Only use calendar functions when users explicitly mention:
                                  - Creating/scheduling new events
                                  - Modifying existing events
                                  - Searching for events
                                  - Calendar management

                                  CALENDAR FUNCTION USAGE:
                                  1. For NEW events (create_calendar_event):
                                     - Use when users want to schedule something new
                                     - Require: title, date, start time, end time
                                     - Optional: description for details/agenda

                                  2. For EXISTING events:
                                     - First use search_events to find the event
                                     - Then use update_calendar_event with the event ID

                                  3. For searches (search_events):
                                     - Search by title, description, date, or time
                                     - Use multiple criteria to narrow results

                                  TIME HANDLING:
                                  - When "today" is mentioned, use: ' . date('Y-m-d') . '
                                  - Use 24-hour format for times (HH:mm)
                                  - Dates should be in YYYY-MM-DD format

                                  After any action:
                                  1. Acknowledge what was done
                                  2. Provide relevant details of the action
                                  3. Wait for further instructions'
                ],
            ];

            $previousMessages = $request->input('messages', []);
            foreach ($previousMessages as $message) {
                $messages[] = [
                    'role' => $message['isUser'] ? 'user' : 'assistant',
                    'content' => $message['content']
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $request->input('prompt')];

            $createdEvents = [];
            $updatedEvents = [];
            $maxIterations = 3;
            $iterations = 0;

            do {
                $iterations++;

                $result = OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'tools' => $tools,
                    'store' => true
                ]);

                $assistantMessage = $result->choices[0]->message;
                $messages[] = $assistantMessage->toArray();

                if (empty($assistantMessage->toolCalls)) {
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

//                    if ($functionName === 'fetch_news') {
//                        if ($functionResult['news']) {
//                            break;
//                        }
//                    } else
                    if ($functionResult['event']) {
                        if ($functionName === 'create_calendar_event') {
                            $createdEvents[] = $functionResult['event'];
                        } else if ($functionName === 'update_calendar_event') {
                            $updatedEvents[] = $functionResult['event'];
                        }
                    }
                }
            } while ($iterations < $maxIterations && empty($createdEvents) && empty($updatedEvents));

            if (!empty($assistantMessage->toolCalls)) {
                $finalResult = OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'tools' => $tools,
                    'store' => true
                ]);
            } else {
                $finalResult = $result;
            }

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
        $query = Event::query();

        if (isset($params->title)) {
            $query->where('title', 'like', '%' . $params->title . '%');
        }

        if (isset($params->description)) {
            $query->where('description', 'like', '%' . $params->description . '%');
        }

        $events = $query->get()->map(function ($event) {
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

        if (isset($params->date)) {
            $events = $events->filter(function ($event) use ($params) {
                return $event['date'] === $params->date;
            });
        }

        if (isset($params->start_time)) {
            $events = $events->filter(function ($event) use ($params) {
                return $event['start_time'] === $params->start_time;
            });
        }

        if (isset($params->end_time)) {
            $events = $events->filter(function ($event) use ($params) {
                return $event['end_time'] === $params->end_time;
            });
        }

        if ($events->isEmpty()) {
            $criteria = [];
            if (isset($params->title)) $criteria[] = "title containing '{$params->title}'";
            if (isset($params->description)) $criteria[] = "description containing '{$params->description}'";
            if (isset($params->date)) $criteria[] = "date {$params->date}";
            if (isset($params->start_time)) $criteria[] = "starting at {$params->start_time}";
            if (isset($params->end_time)) $criteria[] = "ending at {$params->end_time}";
            if (isset($params->id)) $criteria[] = "ID {$params->id}";

            $criteriaStr = implode(', ', $criteria);
            return [
                'message' => "No events found with " . $criteriaStr,
                'event' => null
            ];
        }

        if ($events->count() === 1) {
            $event = $events->first();
            return [
                'message' => "Found event: '{$event['title']}' (ID: {$event['id']}) on {$event['date']} from {$event['start_time']} to {$event['end_time']}" .
                    ($event['description'] ? "\nDescription: {$event['description']}" : ""),
                'event' => $event
            ];
        }

        $eventsList = $events->map(function ($event) {
            return "- '{$event['title']}' (ID: {$event['id']}) on {$event['date']} from {$event['start_time']} to {$event['end_time']}" .
                ($event['description'] ? "\n  Description: {$event['description']}" : "");
        })->join("\n");

        return [
            'message' => "Found multiple matching events:\n{$eventsList}\n\nPlease provide additional details to identify the specific event (e.g., date, start time, description, or ID).",
            'events' => $events->toArray(),
            'event' => null
        ];
    }

    // TODO: AI can refetch the news for a specified category
//    public function fetch_news($params)
//    {
//        //  pass in category
//        $category = $params->category;
//        // call the endpoint with the specified category
//        $refetchedNews = Http::get('https://api.thenewsapi.com/v1/news/top',
//            [
//                'api_token' => env('VITE_THE_NEWS_API'),
//                'categories' => $category
//            ]);
//        dd($refetchedNews->body());
//        // reload the component
//    }
}
