<?php

namespace App\Actions\Risk;

use App\Actions\AI\CallPrismAI;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class EvaluateWithLLMPrism
{
    use AsAction;

    /**
     * Evaluate risk using LLM via Prism
     *
     * @param array<string, mixed> $features
     * @return array{risk_score: int, reasons: array<string>, confidence: string, analysis: string, ai_prompt?: string, ai_response?: string}
     */
    public function handle(array $features): array
    {
        try {
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($features);

            // Use the new CallPrismAI action
            $aiResult = CallPrismAI::run(
                systemPrompt: $systemPrompt,
                userPrompt: $userPrompt,
                maxTokens: 300
            );

            // Check if the AI call was successful
            if (!$aiResult['success']) {
                Log::warning('AI call failed', [
                    'error' => $aiResult['error'] ?? 'Unknown error',
                    'name' => $features['name'] ?? 'Unknown',
                ]);
                return $this->fallbackRuleBasedScoring($features);
            }

            // Get the parsed response
            $parsed = $aiResult['parsed'];

            // If not parsed as JSON, try to handle the raw response
            if (!$parsed && isset($aiResult['response'])) {
                Log::warning('AI response not in expected JSON format', [
                    'response' => substr($aiResult['response'], 0, 500),
                ]);
                return $this->fallbackRuleBasedScoring($features);
            }

            if (!isset($parsed['risk_score'])) {
                Log::warning('Invalid AI response format - missing risk_score', [
                    'response' => $parsed,
                ]);
                return $this->fallbackRuleBasedScoring($features);
            }

            // Build complete prompt for database logging
            $fullPrompt = "System: {$systemPrompt}\n\nUser: {$userPrompt}";

            return [
                'risk_score' => max(0, min(100, (int) $parsed['risk_score'])),
                'reasons' => array_slice($parsed['reasons'] ?? [], 0, 5),
                'confidence' => $parsed['confidence'] ?? 'medium',
                'analysis' => $parsed['analysis'] ?? 'No detailed analysis provided',
                'ai_prompt' => $fullPrompt,
                'ai_response' => $aiResult['response']
            ];

        } catch (\Exception $e) {
            Log::error('Risk evaluation with AI failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

CRITICAL RISK INDICATORS (MUST SCORE 90-100):
- ANY sexual, vulgar, or profane names (e.g., names with "dick", "cock", "pussy", "fuck", etc.) = SCORE 95+
- Names that are sexual jokes or innuendos (e.g., "Lookat MaDick", "Ben Dover", "Mike Hunt") = SCORE 95+
- ANY offensive or harassing content in names/emails = SCORE 90+
- Obviously fake troll names like "Test User", "Ima Bot", "Fake Name" = SCORE 85+
- Email addresses indicating malicious intent (e.g., "gonnaspamyou", "trolling", "fakeuser") = SCORE 85+

HIGH RISK INDICATORS (SCORE 70-90):
- Localhost/private IPs like 127.0.0.1, 0.0.0.0 or 192.168.x.x = SCORE 70+
- Suspicious email patterns like "test", "fake", "bot" in email = SCORE 70+
- Disposable/temporary email services
- VPN/datacenter IP addresses
- Unusual name patterns or test data
- Velocity patterns suggesting automation
- Geographic mismatches
- Phone number anomalies
- Behavioral patterns indicating fraud
- Harassment or abuse indicators

IMPORTANT INSTRUCTIONS:
1. ALWAYS check names for sexual/vulgar content FIRST. "Lookat MaDick" = "Look at my dick" = SCORE 95+
2. Read names OUT LOUD to detect sexual jokes. "Mike Hunt" sounds like "My c*nt" = SCORE 95+
3. Check for word play and innuendo. "Ben Dover" = "Bend over" = SCORE 95+
4. Email addresses with malicious intent are ALWAYS high risk
5. Private IPs (127.0.0.1) are ALWAYS suspicious for real bookings

BE EXTREMELY CONSERVATIVE - It's better to flag legitimate users than to miss trolls/fraudsters.
If a name could possibly be offensive when read aloud or rearranged, SCORE IT HIGH.
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
        if (isset($features['notes']) && !empty($features['notes'])) {
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
            'time_pattern' => 'Submissions follow automated timing'
        ];

        $hasIndicators = false;
        foreach ($features as $key => $value) {
            if (isset($featureDescriptions[$key]) && $value) {
                $prompt .= "- {$featureDescriptions[$key]}\n";
                $hasIndicators = true;
            }
        }

        if (!$hasIndicators) {
            $prompt .= "- No specific risk indicators detected\n";
        }

        $prompt .= "\nAnalyze the booking details and risk indicators. Pay special attention to the actual meaning of the name and email. Provide a risk score and reasons.";

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

        // Check for obvious fake names that might not be in features
        if (isset($features['name'])) {
            $name = strtolower($features['name']);
            if (str_contains($name, 'test') || str_contains($name, 'bot') || str_contains($name, 'fake')) {
                $score += 30;
                $reasons[] = 'Suspicious name detected';
            }
        }

        // Check for obvious fake emails
        if (isset($features['email'])) {
            $email = strtolower($features['email']);
            if (str_contains($email, 'spam') || str_contains($email, 'test') || str_contains($email, 'fake')) {
                $score += 25;
                $reasons[] = 'Suspicious email address';
            }
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
            'analysis' => $analysis
        ];
    }
}