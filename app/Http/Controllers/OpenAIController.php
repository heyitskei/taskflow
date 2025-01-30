<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIController extends Controller
{
    public function openai(Request $request): JsonResponse
    {
        $tools = [
            "type" => "function",
            "function" => [
                "name" => "get_weather",
                "description" => "Get current temperature for a given location.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "location" => [
                            "type" => "string",
                            "description" => "City and country e.g. Bogotá, Colombia"
                        ]
                    ],
                    "required" => [
                        "location"
                    ],
                    "additionalProperties" => false
                ],
                "strict" => true
            ]
        ];

        $messages = [
            ['role' => 'user', 'content' => $request->input('prompt')],
        ];

        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'tools' => [$tools]
        ]);

        $functionName = $result->choices[0]->message->toolCalls[0]->function->name;
        $functionParams = json_decode($result->choices[0]->message->toolCalls[0]->function->arguments);

        $messages[] = $result->choices[0]->message->toArray();
        $messages[] = [
            'role' => 'tool',
            'tool_call_id' => $result->choices[0]->message->toolCalls[0]->id,
            'content' => $this->$functionName($functionParams->location)
        ];

//        dd($messages);
        $result2 = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'tools' => [$tools]
        ]);

        return response()->json($result2);
    }


    public function get_weather($location)
    {
        return "its sunny, everything is fine in $location";
    }
}
