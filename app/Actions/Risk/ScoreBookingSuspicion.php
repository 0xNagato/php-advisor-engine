<?php

namespace App\Actions\Risk;

use App\Actions\Risk\Analyzers\AnalyzeBehavioralSignals;
use App\Actions\Risk\Analyzers\AnalyzeEmailRisk;
use App\Actions\Risk\Analyzers\AnalyzeIPRisk;
use App\Actions\Risk\Analyzers\AnalyzeNameRisk;
use App\Actions\Risk\Analyzers\AnalyzePhoneRisk;
use App\Models\Booking;
use App\Models\RiskAuditLog;
use App\Models\RiskBlacklist;
use App\Models\RiskWhitelist;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class ScoreBookingSuspicion
{
    use AsAction;

    protected array $reasons = [];
    protected array $features = [];
    protected int $score = 0;

    /**
     * Calculate risk score for a booking
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    public function handle(
        string $email,
        string $phone,
        string $name,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $notes = null,
        ?Booking $booking = null
    ): array {
        $this->reasons = [];
        $this->features = [
            'email' => $email,
            'phone' => $phone,
            'name' => $name,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'notes' => $notes,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Check whitelist/blacklist first
        if ($this->checkWhitelist($email, $phone, $ipAddress, $name)) {
            $this->score = 0;
            $this->reasons[] = 'Whitelisted entity';
            return $this->getResult();
        }

        if ($this->checkBlacklist($email, $phone, $ipAddress, $name)) {
            $this->score = 100;
            $this->reasons[] = 'Blacklisted entity';
            return $this->getResult();
        }

        // Analyze individual risk factors
        $emailAnalysis = AnalyzeEmailRisk::run($email);
        $phoneAnalysis = AnalyzePhoneRisk::run($phone);
        $nameAnalysis = AnalyzeNameRisk::run($name);

        // Get region name for IP analysis - venue->region is a string field (region ID)
        $regionName = null;
        if ($booking && $booking->venue) {
            // The region field is a string containing the region ID (e.g., "miami", "ibiza")
            // We can use it directly as the region identifier for IP analysis
            $regionName = $booking->venue->region;
        }

        $ipAnalysis = $ipAddress ? AnalyzeIPRisk::run($ipAddress, $regionName) : ['score' => 0, 'reasons' => [], 'features' => []];
        $behavioralAnalysis = AnalyzeBehavioralSignals::run($email, $phone, $ipAddress, $notes, $booking);

        // Log detailed breakdown for debugging
        Log::info('Risk Score Breakdown', [
            'booking_id' => $booking?->id,
            'email_analysis' => [
                'score' => $emailAnalysis['score'],
                'reasons' => $emailAnalysis['reasons'],
                'email' => $email
            ],
            'phone_analysis' => [
                'score' => $phoneAnalysis['score'],
                'reasons' => $phoneAnalysis['reasons'],
                'phone' => $phone
            ],
            'name_analysis' => [
                'score' => $nameAnalysis['score'],
                'reasons' => $nameAnalysis['reasons'],
                'name' => $name
            ],
            'ip_analysis' => [
                'score' => $ipAnalysis['score'],
                'reasons' => $ipAnalysis['reasons'],
                'ip' => $ipAddress
            ],
            'behavioral_analysis' => [
                'score' => $behavioralAnalysis['score'],
                'reasons' => $behavioralAnalysis['reasons']
            ]
        ]);

        // Count high-risk indicators
        $highRiskCount = 0;
        if ($emailAnalysis['score'] >= 80) $highRiskCount++;
        if ($nameAnalysis['score'] >= 80) $highRiskCount++;
        if ($ipAnalysis['score'] >= 80) $highRiskCount++;
        if ($behavioralAnalysis['score'] >= 80) $highRiskCount++;

        // Store whether we have extreme profanity
        $hasExtremeProfanity = ($emailAnalysis['score'] >= 100 || $nameAnalysis['score'] >= 90);

        // If multiple extreme red flags, use maximum score approach
        if ($highRiskCount >= 2) {
            // Multiple extreme red flags - take the highest score
            $this->score = max(
                $emailAnalysis['score'],
                $nameAnalysis['score'],
                $ipAnalysis['score'],
                $behavioralAnalysis['score'],
                80  // Minimum for multiple extreme risks
            );
        } else {
            // Normal weighted scoring for less extreme cases
            $this->score = (int) round(
                $emailAnalysis['score'] * 0.25 +
                $phoneAnalysis['score'] * 0.25 +
                $nameAnalysis['score'] * 0.15 +
                $ipAnalysis['score'] * 0.20 +
                $behavioralAnalysis['score'] * 0.15
            );
        }

        // Merge reasons and features
        $this->reasons = array_merge(
            $this->reasons,
            $emailAnalysis['reasons'],
            $phoneAnalysis['reasons'],
            $nameAnalysis['reasons'],
            $ipAnalysis['reasons'],
            $behavioralAnalysis['reasons']
        );

        $this->features = array_merge(
            $this->features,
            $emailAnalysis['features'],
            $phoneAnalysis['features'],
            $nameAnalysis['features'],
            $ipAnalysis['features'],
            $behavioralAnalysis['features']
        );

        // Store individual analyzer results for breakdown
        $this->features['breakdown'] = [
            'email' => [
                'score' => $emailAnalysis['score'],
                'reasons' => $emailAnalysis['reasons'],
                'features' => $emailAnalysis['features'],
            ],
            'phone' => [
                'score' => $phoneAnalysis['score'],
                'reasons' => $phoneAnalysis['reasons'],
                'features' => $phoneAnalysis['features'],
            ],
            'name' => [
                'score' => $nameAnalysis['score'],
                'reasons' => $nameAnalysis['reasons'],
                'features' => $nameAnalysis['features'],
            ],
            'ip' => [
                'score' => $ipAnalysis['score'],
                'reasons' => $ipAnalysis['reasons'],
                'features' => $ipAnalysis['features'],
            ],
            'behavioral' => [
                'score' => $behavioralAnalysis['score'],
                'reasons' => $behavioralAnalysis['reasons'],
                'features' => $behavioralAnalysis['features'],
            ],
        ];

        // Apply LLM heuristics if enabled - run on ALL bookings
        $llmUsed = false;
        $llmResponse = null;
        $aiPrompt = null;
        $aiResponseRaw = null;
        if (config('app.ai_screening_enabled', false)) {
            try {
                // Use Prism-based LLM evaluation
                $llmScore = EvaluateWithLLMPrism::run($this->features);
                $llmUsed = true;
                $llmResponse = json_encode($llmScore);

                // Store AI prompt and response for database logging
                $aiPrompt = $llmScore['ai_prompt'] ?? null;
                $aiResponseRaw = $llmScore['ai_response'] ?? null;

                // ALWAYS consider AI score for ALL bookings
                // Use weighted combination: 70% rules, 30% AI
                $originalScore = $this->score;
                $this->score = (int) round($this->score * 0.7 + $llmScore['risk_score'] * 0.3);

                // But if AI detects high risk that rules missed, use the higher score
                if ($llmScore['risk_score'] >= 70 && $originalScore < 30) {
                    // AI found something very suspicious that rules missed
                    $this->score = max($this->score, $llmScore['risk_score']);
                    $this->reasons[] = 'AI detected high-risk patterns';
                }

                if (!empty($llmScore['reasons'])) {
                    $this->reasons[] = 'AI analysis: ' . implode(', ', $llmScore['reasons']);
                }
            } catch (\Exception $e) {
                Log::warning('LLM heuristic failed, using rules-only score', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking?->id,
                ]);
                // Continue with rules-only score
            }
        }

        // Cap score at 100
        $this->score = min(100, $this->score);

        // Apply minimum score for extreme profanity AFTER all calculations
        if ($hasExtremeProfanity && $this->score < 70) {
            $this->score = 70;  // Minimum score for extreme profanity
            $this->reasons[] = 'Minimum score applied due to extreme profanity';
        }

        // Store LLM usage info including AI prompt and response
        $this->features['llm_used'] = $llmUsed;
        $this->features['llm_response'] = $llmResponse;
        if ($aiPrompt) {
            $this->features['ai_prompt'] = $aiPrompt;
        }
        if ($aiResponseRaw) {
            $this->features['ai_response'] = $aiResponseRaw;
        }

        return $this->getResult();
    }

    /**
     * Check if any entity is whitelisted
     */
    protected function checkWhitelist(string $email, string $phone, ?string $ipAddress, string $name): bool
    {
        // Check email domain
        $emailDomain = substr(strrchr($email, '@'), 1);
        if ($emailDomain && RiskWhitelist::isWhitelisted(RiskWhitelist::TYPE_DOMAIN, $emailDomain)) {
            return true;
        }

        // Check phone
        if (RiskWhitelist::isWhitelisted(RiskWhitelist::TYPE_PHONE, $phone)) {
            return true;
        }

        // Check IP
        if ($ipAddress && RiskWhitelist::isWhitelisted(RiskWhitelist::TYPE_IP, $ipAddress)) {
            return true;
        }

        // Check name
        if (RiskWhitelist::isWhitelisted(RiskWhitelist::TYPE_NAME, $name)) {
            return true;
        }

        return false;
    }

    /**
     * Check if any entity is blacklisted
     */
    protected function checkBlacklist(string $email, string $phone, ?string $ipAddress, string $name): bool
    {
        // Check email domain
        $emailDomain = substr(strrchr($email, '@'), 1);
        if ($emailDomain && RiskBlacklist::isBlacklisted(RiskBlacklist::TYPE_DOMAIN, $emailDomain)) {
            $this->features['blacklist_match'] = 'email_domain';
            return true;
        }

        // Check phone
        if (RiskBlacklist::isBlacklisted(RiskBlacklist::TYPE_PHONE, $phone)) {
            $this->features['blacklist_match'] = 'phone';
            return true;
        }

        // Check IP
        if ($ipAddress && RiskBlacklist::isBlacklisted(RiskBlacklist::TYPE_IP, $ipAddress)) {
            $this->features['blacklist_match'] = 'ip';
            return true;
        }

        // Check name
        if (RiskBlacklist::isBlacklisted(RiskBlacklist::TYPE_NAME, $name)) {
            $this->features['blacklist_match'] = 'name';
            return true;
        }

        return false;
    }

    /**
     * Get the scoring result
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    protected function getResult(): array
    {
        return [
            'score' => $this->score,
            'reasons' => array_unique($this->reasons),
            'features' => $this->features,
        ];
    }
}