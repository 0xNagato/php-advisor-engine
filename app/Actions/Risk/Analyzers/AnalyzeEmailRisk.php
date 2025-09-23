<?php

namespace App\Actions\Risk\Analyzers;

use Lorisleiva\Actions\Concerns\AsAction;

class AnalyzeEmailRisk
{
    use AsAction;

    protected array $disposableDomains = [
        'mailinator.com', 'guerrillamail.com', '10minutemail.com',
        'tempmail.com', 'throwaway.email', 'yopmail.com',
        'maildrop.cc', 'mintemail.com', 'temp-mail.org',
        'fakeinbox.com', 'sharklasers.com', 'guerrillamail.info',
        'spam4.me', 'grr.la', 'mailnesia.com', 'tempmailaddress.com',
        'dispostable.com', 'slipry.net', 'jetable.org', 'temporaryemail.net',
    ];

    // Weighted profanity - emails with these are almost always abusive
    protected array $profanityPatterns = [
        // Extreme (100 points)
        'fuck' => 100,
        'shit' => 100,
        'cock' => 100,
        'pussy' => 100,
        'cunt' => 100,
        'whore' => 100,
        'slut' => 100,
        'bitch' => 100,
        'dick' => 80,  // Could be legitimate name but suspicious
        'asshole' => 100,

        // High (80 points)
        'piss' => 80,
        'bastard' => 80,
        'suck' => 80,  // In email, usually intentional

        // Medium (60 points)
        'ass' => 60,
        'blow' => 60,
        'damn' => 60,
        'hell' => 60,
        'crap' => 60,

        // Medical (40 points) - still suspicious in email
        'penis' => 40,
        'vagina' => 40,
    ];

    // Test patterns that indicate fake/test emails
    protected array $testPatterns = [
        'test', 'testing', 'demo', 'example', 'sample',
        'fake', 'bot', 'robot', 'user', 'guest',
        'asdf', 'qwerty', 'abc', 'xyz', 'foo', 'bar',
    ];

    /**
     * Analyze email for risk indicators
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    public function handle(string $email): array
    {
        $score = 0;
        $reasons = [];
        $features = [];

        // Sanitize and validate email input
        $email = strtolower(trim($email));

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'score' => 100,
                'reasons' => ['Invalid email format'],
                'features' => ['email_valid' => false],
            ];
        }

        [$localPart, $domain] = explode('@', $email);

        // Check disposable domain
        if (in_array($domain, $this->disposableDomains)) {
            $score += 40;
            $reasons[] = 'Disposable email domain';
            $features['disposable_email'] = true;
        }

        // Check for plus addressing (not necessarily bad, but can be indicator)
        if (str_contains($localPart, '+')) {
            $score += 10;
            $reasons[] = 'Plus addressing in email';
            $features['plus_addressing'] = true;
        }

        // Check for excessive dots
        if (substr_count($localPart, '.') > 3) {
            $score += 15;
            $reasons[] = 'Excessive dots in email';
            $features['excessive_dots'] = true;
        }

        // Check for no-reply patterns
        if (preg_match('/^(no-?reply|noreply|do-?not-?reply)/i', $localPart)) {
            $score += 25;
            $reasons[] = 'No-reply email pattern';
            $features['noreply_pattern'] = true;
        }

        // Check for test patterns in username
        foreach ($this->testPatterns as $pattern) {
            if (str_contains($localPart, (string) $pattern)) {
                $score += 50; // Test patterns are highly suspicious
                $reasons[] = 'Test pattern in email username';
                $features['test_email'] = true;
                break;
            }
        }

        // Check for gibberish ratio
        if ($this->isGibberish($localPart)) {
            $score += 20;
            $reasons[] = 'Gibberish email username';
            $features['gibberish_email'] = true;
        }

        // Check for numeric-only local part
        if (preg_match('/^[0-9]+$/', $localPart)) {
            $score += 15;
            $reasons[] = 'Numeric-only email username';
            $features['numeric_email'] = true;
        }

        // Check for profanity in email with weighted scoring
        $emailLower = strtolower($email);
        $profanityScore = 0;
        foreach ($this->profanityPatterns as $profanity => $weight) {
            if (str_contains($emailLower, $profanity)) {
                // For context-sensitive words in email, check word boundaries
                if (in_array($profanity, ['hell', 'ass', 'blow', 'cock'])) {
                    $position = strpos($emailLower, (string) $profanity);
                    $beforeChar = $position > 0 ? $emailLower[$position - 1] : '@';
                    $afterPos = $position + strlen($profanity);
                    $afterChar = $afterPos < strlen($emailLower) ? $emailLower[$afterPos] : '@';

                    // Skip if part of a larger word
                    if (ctype_alnum($beforeChar) || ctype_alnum($afterChar)) {
                        continue;
                    }
                }
                $profanityScore = max($profanityScore, $weight);
            }
        }

        if ($profanityScore > 0) {
            $score += min(100, $profanityScore);
            $features['profane_email'] = true;

            if ($profanityScore >= 100) {
                $reasons[] = 'Extreme profanity in email';
            } elseif ($profanityScore >= 60) {
                $reasons[] = 'Offensive/profane email address';
            } else {
                $reasons[] = 'Inappropriate email address';
            }
        }

        // Check MX records (stub for now)
        $features['mx_valid'] = $this->checkMXRecord($domain);
        if (! $features['mx_valid']) {
            $score += 35; // Increased - invalid MX records are suspicious
            $reasons[] = 'No valid MX records';
        }

        $features['email_domain'] = $domain;
        $features['email_local'] = $localPart;

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
            'features' => $features,
        ];
    }

    /**
     * Check if string appears to be gibberish
     */
    protected function isGibberish(string $text): bool
    {
        // Remove numbers
        $text = preg_replace('/[0-9]/', '', $text);

        if (strlen((string) $text) < 4) {
            return false;
        }

        // Check for too many consonants in a row
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/i', (string) $text)) {
            return true;
        }

        // Check for repeating patterns
        if (preg_match('/(.)\1{3,}/', (string) $text)) {
            return true;
        }

        // Check for lack of vowels
        $vowelCount = preg_match_all('/[aeiou]/i', (string) $text);
        $consonantCount = preg_match_all('/[bcdfghjklmnpqrstvwxyz]/i', (string) $text);

        if ($consonantCount > 0 && $vowelCount / $consonantCount < 0.2) {
            return true;
        }

        return false;
    }

    /**
     * Check MX record for domain (simplified stub)
     */
    protected function checkMXRecord(string $domain): bool
    {
        // In production, this would do actual DNS lookup
        // For now, assume common domains are valid
        $knownValidDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'icloud.com', 'aol.com', 'protonmail.com', 'mail.com',
        ];

        return in_array($domain, $knownValidDomains) || checkdnsrr($domain, 'MX');
    }
}
