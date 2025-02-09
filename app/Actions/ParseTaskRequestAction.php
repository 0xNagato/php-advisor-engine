<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;

class ParseTaskRequestAction extends Action
{
    /**
     * Parse the boss's chat message using the OpenAI API.
     */
    public function handle(string $requestMessage): ?array
    {
        $openaiApiKey = config('services.openai.api_key');

        Log::info('Starting task parsing', ['message' => $requestMessage]);

        $prompt = "You are an assistant that converts a boss's chat message into a structured Asana task.
You should combine all context and modifications into a single coherent task.
Return the result as a single-line JSON string in the following format:
{\"name\": \"Task Name\", \"notes\": \"Detailed description\"}

The task name should be concise but descriptive.
The notes should include all details and modifications mentioned.
Extract the task name and description from the following message:
\"$requestMessage\"";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$openaiApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.2,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $assistantReply = $data['choices'][0]['message']['content'] ?? null;

            Log::info('OpenAI API response received', [
                'status' => $response->status(),
                'response' => $assistantReply,
            ]);

            if ($assistantReply) {
                // Clean up the response by removing newlines and extra whitespace
                $cleanedReply = preg_replace('/\s+/', ' ', trim($assistantReply));
                // Remove any JSON code block markers if present
                $cleanedReply = preg_replace('/```json\s*|\s*```/', '', $cleanedReply);

                $parsed = json_decode($cleanedReply, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    Log::info('Successfully parsed task', ['parsed_data' => $parsed]);

                    return $parsed;
                }

                Log::error('JSON parsing failed', [
                    'error' => json_last_error_msg(),
                    'response' => $assistantReply,
                    'cleaned_response' => $cleanedReply,
                ]);
            }
        } else {
            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }
}
