<?php

namespace App\Actions\Risk;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class EvaluateWithLLM
{
    use AsAction;

    /**
     * Evaluate risk using LLM heuristics
     *
     * @param  array<string, mixed>  $features
     * @return array{risk_score: int, reasons: array<string>, confidence: string, analysis: string}
     */
    public function handle(array $features): array
    {
        try {
            // Call LLM API with all features for better context
            $response = $this->callLLMAPI($features);

            return [
                'risk_score' => $response['risk_score'] ?? 50,
                'reasons' => $response['reasons'] ?? [],
                'confidence' => $response['confidence'] ?? 'medium',
                'analysis' => $response['analysis'] ?? 'No detailed analysis available',
            ];
        } catch (Exception $e) {
            Log::error('LLM evaluation failed', [
                'error' => $e->getMessage(),
            ]);

            // Return neutral score on failure
            return [
                'risk_score' => 50,
                'reasons' => [],
                'confidence' => 'low',
                'analysis' => 'LLM evaluation failed',
            ];
        }
    }

    /**
     * Call LLM API for risk evaluation
     */
    protected function callLLMAPI(array $features): array
    {
        $apiKey = config('services.openai.key');
        $apiUrl = config('services.openai.url', 'https://api.openai.com/v1/chat/completions');
        $model = config('services.openai.model', 'gpt-4o-mini');

        // If no API key configured, fall back to rule-based scoring
        if (! $apiKey) {
            return $this->fallbackRuleBasedScoring($features);
        }

        try {
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($features);

            // Debug logging - log what we're sending to AI
            Log::info('Sending to AI for risk evaluation', [
                'user_prompt' => $userPrompt,
                'features' => $features,
                'model' => $model,
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.2, // Lower temperature for consistent scoring
                    'max_completion_tokens' => 300,  // Updated for newer models
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->successful()) {
                Log::warning('LLM API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackRuleBasedScoring($features);
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            // Debug logging - log what we received from AI
            Log::info('Received from AI risk evaluation', [
                'raw_response' => $content,
                'parsed_response' => $parsed,
                'name' => $features['name'] ?? 'Unknown',
                'email' => $features['email'] ?? 'Unknown',
            ]);

            if (! isset($parsed['risk_score'])) {
                Log::warning('Invalid LLM response format', ['response' => $content]);

                return $this->fallbackRuleBasedScoring($features);
            }

            return [
                'risk_score' => max(0, min(100, (int) $parsed['risk_score'])),
                'reasons' => array_slice($parsed['reasons'] ?? [], 0, 5), // Limit to 5 reasons
                'confidence' => $parsed['confidence'] ?? 'medium',
                'analysis' => $parsed['analysis'] ?? 'No detailed analysis provided',
            ];

        } catch (Exception $e) {
            Log::error('LLM API call failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fallbackRuleBasedScoring($features);
        }
    }

    /**
     * Build the system prompt for the LLM
     */
    protected function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert fraud detection system for a restaurant reservation platform. Your task is to analyze booking features and determine the risk level of fraudulent or problematic bookings.

Analyze the provided features and return a JSON response with:
1. "risk_score": An integer from 0-100 where:
   - 0-29: Low risk (likely legitimate)
   - 30-69: Medium risk (requires review)
   - 70-100: High risk (likely fraudulent)

2. "reasons": An array of 1-5 concise reasons explaining the risk factors

3. "confidence": A string indicating confidence level ("low", "medium", "high")

4. "analysis": A brief (1-2 sentences) analysis of the overall risk assessment

Consider these risk indicators:
- Offensive, profane, or inappropriate names/emails (MAJOR RED FLAG - score 70+)
- Obviously fake names like "Test User", "Ima Bot", "Fake Name" (HIGH RISK - score 70+)
- Suspicious email local parts like "spam", "test", "fake", "bot" (HIGH RISK - score 60+)
- Localhost/private IPs like 127.0.0.1 or 192.168.x.x (HIGH RISK - score 70+)
- Disposable/temporary email services
- VPN/datacenter IP addresses
- Unusual name patterns or test data
- Velocity patterns suggesting automation
- Geographic mismatches
- Phone number anomalies
- Behavioral patterns indicating fraud
- Harassment or abuse indicators

Be conservative - it's better to flag for review than to miss fraud.
Always return valid JSON matching this structure:
{"risk_score": 50, "reasons": ["Example reason 1", "Example reason 2"], "confidence": "medium", "analysis": "Brief analysis of the risk assessment"}
PROMPT;
    }

    /**
     * Build the user prompt with features
     */
    protected function buildUserPrompt(array $features): string
    {
        $prompt = "Analyze this restaurant booking for fraud risk:\n\n";

        // Include actual data for context
        if (isset($features['email'])) {
            $prompt .= "Email: {$features['email']}\n";
        }
        if (isset($features['phone'])) {
            $prompt .= "Phone: {$features['phone']}\n";
        }
        if (isset($features['name'])) {
            $prompt .= "Name: {$features['name']}\n";
        }
        if (isset($features['ip'])) {
            $prompt .= "IP Address: {$features['ip']}\n";
        }
        if (isset($features['notes']) && ! empty($features['notes'])) {
            $prompt .= "Booking Notes: {$features['notes']}\n";
        }

        $prompt .= "\nDetected Risk Indicators:\n";

        $featureDescriptions = [
            'disposable_email' => 'Using disposable email domain',
            'gibberish_email' => 'Email username appears randomly generated',
            'noreply_pattern' => 'Email follows no-reply pattern',
            'mx_valid' => 'Email domain has valid MX records',
            'repeating_pattern' => 'Phone has repeating digit pattern',
            'sequential_digits' => 'Phone has sequential digits',
            'test_number' => 'Phone appears to be test number',
            'voip' => 'Phone is VoIP number',
            'nanp_valid' => 'Phone follows valid North American format',
            'repeated_tokens' => 'Name has repeated words',
            'single_letter' => 'Name uses single letters',
            'test_name' => 'Name matches test patterns',
            'gibberish' => 'Name appears randomly generated',
            'datacenter_ip' => 'IP from datacenter/hosting provider',
            'private_ip' => 'IP is private/local address',
            'tor_exit' => 'IP is Tor exit node',
            'geo_mismatch' => 'IP location doesn\'t match venue region',
            'velocity_burst' => 'Multiple bookings from same source',
            'submission_burst' => 'Rapid form submissions detected',
            'identical_notes' => 'Duplicate booking notes across sessions',
            'venue_hopping' => 'Multiple venues targeted quickly',
            'device_velocity' => 'High activity from single device',
            'time_pattern' => 'Submissions follow automated timing',
        ];

        $hasIndicators = false;
        foreach ($features as $key => $value) {
            if (isset($featureDescriptions[$key]) && $value) {
                $prompt .= "- {$featureDescriptions[$key]}\n";
                $hasIndicators = true;
            }
        }

        if (! $hasIndicators) {
            $prompt .= "- No specific risk indicators detected\n";
        }

        $prompt .= "\nAnalyze the booking details and risk indicators. Provide a risk score and reasons.";

        return $prompt;
    }

    /**
     * Fallback rule-based scoring when LLM is unavailable
     */
    protected function fallbackRuleBasedScoring(array $features): array
    {
        $score = 0;
        $reasons = [];

        // High-risk indicators (15-25 points each)
        if (isset($features['disposable_email']) && $features['disposable_email']) {
            $score += 25;
            $reasons[] = 'Disposable email service detected';
        }

        if (isset($features['test_number']) && $features['test_number']) {
            $score += 25;
            $reasons[] = 'Test phone number pattern';
        }

        if (isset($features['velocity_burst']) && $features['velocity_burst']) {
            $score += 20;
            $reasons[] = 'Rapid submission velocity';
        }

        if (isset($features['tor_exit']) && $features['tor_exit']) {
            $score += 20;
            $reasons[] = 'Tor network usage';
        }

        // Medium-risk indicators (10-15 points each)
        if (isset($features['datacenter_ip']) && $features['datacenter_ip']) {
            $score += 15;
            $reasons[] = 'Datacenter IP address';
        }

        if (isset($features['gibberish_email']) && $features['gibberish_email']) {
            $score += 15;
            $reasons[] = 'Suspicious email pattern';
        }

        if (isset($features['test_name']) && $features['test_name']) {
            $score += 15;
            $reasons[] = 'Test name detected';
        }

        if (isset($features['venue_hopping']) && $features['venue_hopping']) {
            $score += 15;
            $reasons[] = 'Multiple venue attempts';
        }

        // Low-risk indicators (5-10 points each)
        if (isset($features['repeating_pattern']) && $features['repeating_pattern']) {
            $score += 10;
            $reasons[] = 'Unusual phone pattern';
        }

        if (isset($features['geo_mismatch']) && $features['geo_mismatch']) {
            $score += 10;
            $reasons[] = 'Geographic mismatch';
        }

        if (isset($features['voip']) && $features['voip']) {
            $score += 5;
            $reasons[] = 'VoIP number used';
        }

        // Determine confidence based on score clarity
        $confidence = 'medium';
        if ($score >= 70 || $score <= 20) {
            $confidence = 'high';
        } elseif ($score >= 40 && $score <= 60) {
            $confidence = 'low';
        }

        // Generate analysis based on score
        $analysis = match (true) {
            $score >= 70 => 'High risk indicators detected. Manual review strongly recommended.',
            $score >= 30 => 'Moderate risk indicators present. Review recommended.',
            default => 'Low risk profile. Likely legitimate booking.'
        };

        return [
            'risk_score' => min(100, $score),
            'reasons' => array_slice($reasons, 0, 5),
            'confidence' => $confidence,
            'analysis' => $analysis,
        ];
    }
}
