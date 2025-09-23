<?php

namespace App\Actions\Risk;

use App\Actions\AI\CallPrismAI;
use Exception;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class EvaluateWithLLMPrism
{
    use AsAction;

    /**
     * Evaluate risk using LLM via Prism
     *
     * @param  array<string, mixed>  $features
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
            if (! $aiResult['success']) {
                Log::warning('AI call failed', [
                    'error' => $aiResult['error'] ?? 'Unknown error',
                    'name' => $features['name'] ?? 'Unknown',
                ]);

                return $this->fallbackRuleBasedScoring($features);
            }

            // Get the parsed response
            $parsed = $aiResult['parsed'];

            // If not parsed as JSON, try to handle the raw response
            if (! $parsed && isset($aiResult['response'])) {
                Log::warning('AI response not in expected JSON format', [
                    'response' => substr((string) $aiResult['response'], 0, 500),
                ]);

                return $this->fallbackRuleBasedScoring($features);
            }

            if (! isset($parsed['risk_score'])) {
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
                'ai_response' => $aiResult['response'],
            ];

        } catch (Exception $e) {
            Log::error('Risk evaluation with AI failed', [
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
   - 0-39: Low risk (likely legitimate)
   - 40-69: Medium risk (requires review)
   - 70-100: High risk (likely fraudulent)

2. "reasons": An array of 1-5 concise reasons explaining the risk factors

3. "confidence": A string indicating confidence level ("low", "medium", "high")

4. "analysis": A brief (1-2 sentences) analysis of the overall risk assessment

CRITICAL RISK INDICATORS (SCORE 80-100):
- Obvious sexual/vulgar names with clear malicious intent (e.g., "Fuck You", "Dick Head", "Ass Hole") = SCORE 95+
- Names that are clear sexual jokes with no legitimate meaning (e.g., "Suck My Balls", "Eat My Pussy") = SCORE 95+
- Emails with obvious malicious intent (e.g., "gonnaspamyou@domain.com", "fuckyou@domain.com") = SCORE 90+
- Names that are clearly fake and troll-like ("Test User", "Fake Person", "Robot Bot") = SCORE 80+

HIGH RISK INDICATORS (SCORE 60-80):
- Private IPs like 127.0.0.1, 0.0.0.0 or 192.168.x.x = SCORE 70+
- Emails with "test", "fake", "bot" in username (not domain) = SCORE 60+
- Names with obvious profanity in clear context = SCORE 70+
- Multiple failed booking attempts (5+) = SCORE 70+

MEDIUM RISK INDICATORS (SCORE 20-50):
- Datacenter/VPN IP addresses (common for business users) = SCORE 20-30
- Business email domains (company.com, business.net, etc.) = SCORE 0-10
- Multiple bookings from same IP/device (3-10 per hour) = SCORE 10-30
- Geographic location different from venue = SCORE 5-15
- VoIP or unusual phone number formats = SCORE 10-20

LEGITIMATE PATTERNS TO CONSIDER LOW RISK:
- Business email addresses (firstname.lastname@company.com, @company.com domains)
- Professional names (Michael Smith, John Johnson, Paulo Althoff, etc.)
- Corporate VPN/datacenter IPs (AWS, Google Cloud, Azure ranges)
- Multiple bookings from same device/IP (3-10 per hour is normal concierge activity)
- International phone numbers with proper formatting
- Booking notes mentioning "business dinner", "client meeting", "team celebration", "corporate event"
- Email domains: @1hotels.com, @blockchainproductagency.com, @gpafactoring.com.br (legitimate businesses)
- Private IPs (127.0.0.1, 192.168.x.x) during testing/development (should not be heavily penalized)

IMPORTANT INSTRUCTIONS:
1. BE BUSINESS-FRIENDLY: Many legitimate users have business emails, VPNs, and make multiple bookings
2. CONTEXT MATTERS: "Dick" as first name might be legitimate, "Dick Head" is clearly malicious
3. CONCIERGE ACTIVITY: Multiple bookings from same device/IP is often legitimate business
4. PROFESSIONAL CONTEXT: Business emails and professional names are usually legitimate
5. BALANCED APPROACH: It's better to miss some fraud than to block legitimate business users

READ NAMES CAREFULLY: "Shaz Peksos" might sound like "shah's peck sauce" - that's legitimate, not offensive.
"Asshogaga" might be a legitimate name or domain - don't assume malice just because it sounds similar to profanity.

NOTES CONTEXT: Booking notes are often legitimate business communications from concierges or customers. Only flag notes if they contain obvious malicious content like threats, spam, or test data. Professional notes, standard booking details, and concierge templates should not be penalized.

Always return valid JSON matching this structure:
{"risk_score": 30, "reasons": ["Example reason"], "confidence": "medium", "analysis": "Brief analysis of the risk assessment"}
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

        // Medium-risk indicators (5-10 points each)
        // Datacenter IPs are common for legitimate business users
        if (isset($features['datacenter_ip']) && $features['datacenter_ip']) {
            $score += 5;  // Greatly reduced - many legitimate users use VPNs
            $reasons[] = 'Datacenter/VPN IP address (common for business users)';
        }

        if (isset($features['gibberish_email']) && $features['gibberish_email']) {
            $score += 15;
            $reasons[] = 'Suspicious email pattern';
        }

        if (isset($features['test_name']) && $features['test_name']) {
            $score += 40; // Increased - test names are suspicious
            $reasons[] = 'Test name pattern detected';
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
                $score += 35; // Increased - suspicious emails should be flagged
                $reasons[] = 'Suspicious email address';
            }
        }

        // Check for obvious fake names that might not be caught by features
        if (isset($features['name'])) {
            $name = strtolower($features['name']);
            if (str_contains($name, 'test') || str_contains($name, 'fake') || str_contains($name, 'bot')) {
                $score += 40; // Increased - obvious fake names should be flagged
                $reasons[] = 'Obvious fake name detected';
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
            'analysis' => $analysis,
        ];
    }
}
