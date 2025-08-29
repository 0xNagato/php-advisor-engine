<?php

namespace App\Actions\Venue;

use Exception;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;

class ParseVenueScheduleWithClaude
{
    use AsAction;

    private const int MAX_RETRIES = 3;

    private const string CLAUDE_PROMPT = <<<'EOT'
        You are a parser for restaurant schedules. Given the following schedule text, extract:
        1. Venue names
        2. Operating days (if specified)
        3. Operating hours (assume 5:00 PM - 10:30 PM if not specified)
        4. Prime time slots per day

        Important time slot rules:
        - All times must align to 30-minute increments (e.g., :00 or :30)
        - If an end time doesn't align to a 30-minute increment, round UP to the next increment
        - Example: 8:45 PM should become 9:00 PM

        For each venue, specify which days are open/closed and the prime time slots.
        If a time range mentions "close", use "22:30" as the end time.
        If "All hours are non-prime" is specified, return an empty prime_slots array.

        Format as JSON:
        {
            "venues": [
                {
                    "name": "string",
                    "schedule": {
                        "monday": {
                            "is_open": boolean,
                            "open_time": "17:00",
                            "close_time": "22:30",
                            "prime_slots": [
                                {
                                    "start": "HH:00/30",
                                    "end": "HH:00/30"
                                }
                            ]
                        }
                        // ... repeat for all days
                    }
                }
            ]
        }

        Input text:
        {text}
    EOT;

    public function handle(string $text): array
    {
        try {
            $response = $this->callClaude($text);

            return $this->parseResponse($response);
        } catch (Exception $e) {
            throw new Exception('Failed to parse schedule: '.$e->getMessage());
        }
    }

    private function callClaude(string $text): array
    {
        $response = Http::withHeaders([
            'anthropic-version' => '2023-06-01',
            'x-api-key' => config('services.anthropic.api_key'),
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-opus-4-1-20250805',
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => str_replace('{text}', $text, self::CLAUDE_PROMPT),
                ],
            ],
        ])->json();

        throw_unless(isset($response['content'][0]['text']), new Exception('Empty response from Claude'));

        return $response;
    }

    private function parseResponse(array $response): array
    {
        $content = $response['content'][0]['text'];

        if (str_contains((string) $content, '```json')) {
            preg_match('/```json\n(.*)\n```/s', (string) $content, $matches);
            $content = $matches[1] ?? $content;
        }

        $parsedData = json_decode((string) $content, true);
        throw_if(! $parsedData || ! isset($parsedData['venues']), new Exception('Invalid JSON structure'));

        return $parsedData;
    }
}
