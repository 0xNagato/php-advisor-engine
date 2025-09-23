<?php

namespace App\Actions\AI;

use Exception;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Prism\Prism\Prism;

class CallPrismAI
{
    use AsAction;

    /**
     * Call AI via Prism with comprehensive logging
     *
     * @return array{success: bool, response?: string, parsed?: array, error?: string, request_log: array, response_log: array}
     */
    public function handle(
        string $systemPrompt,
        string $userPrompt,
        ?string $provider = null,
        ?string $model = null,
        int $maxTokens = 300
    ): array {
        // Get configuration
        $provider ??= config('services.prism.provider', 'openai');
        $model ??= config('services.prism.model', 'gpt-5-mini');

        // Build request log
        $requestLog = [
            'provider' => $provider,
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'timestamp' => now()->toIso8601String(),
        ];

        // Log the request
        Log::info('Prism AI Request', [
            'provider' => $provider,
            'model' => $model,
            'prompt_length' => strlen($userPrompt),
            'system_prompt_length' => strlen($systemPrompt),
        ]);

        try {
            // Check API key configuration
            $apiKey = $this->getApiKey($provider);
            throw_unless($apiKey, new Exception("No API key configured for provider: {$provider}"));

            // Make the API call
            $response = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withMaxTokens($maxTokens)
                ->asText();

            $responseText = $response->text;

            // Build response log
            $responseLog = [
                'raw_response' => $responseText,
                'response_length' => strlen($responseText),
                'timestamp' => now()->toIso8601String(),
            ];

            // Try to parse JSON if it looks like JSON
            $parsed = null;
            $jsonContent = $responseText;

            // Remove markdown code blocks if present
            if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $jsonContent, $matches)) {
                $jsonContent = $matches[1];
            }

            // Try to parse JSON
            if (str_starts_with(trim($jsonContent), '{') || str_starts_with(trim($jsonContent), '[')) {
                $parsed = json_decode($jsonContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('Failed to parse AI response as JSON', [
                        'error' => json_last_error_msg(),
                        'response' => substr($responseText, 0, 500),
                    ]);
                }
            }

            // Log successful response
            Log::info('Prism AI Response', [
                'provider' => $provider,
                'model' => $model,
                'response_length' => strlen($responseText),
                'is_json' => $parsed !== null,
            ]);

            return [
                'success' => true,
                'response' => $responseText,
                'parsed' => $parsed,
                'request_log' => $requestLog,
                'response_log' => $responseLog,
            ];

        } catch (Exception $e) {
            // Log the error
            Log::error('Prism AI Call Failed', [
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'request_log' => $requestLog,
                'response_log' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }
    }

    /**
     * Get API key for the specified provider
     */
    private function getApiKey(string $provider): ?string
    {
        return config("prism.providers.{$provider}.api_key");
    }
}
