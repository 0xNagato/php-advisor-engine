<?php

namespace App\Actions\Risk\Analyzers;

use Lorisleiva\Actions\Concerns\AsAction;

class AnalyzeNameRisk
{
    use AsAction;

    protected array $testNames = [
        // Obviously fake test names - should be HIGH risk
        'test', 'testing', 'demo', 'example', 'sample',
        'asdf', 'qwerty', 'abc', 'xyz', 'foo',
        'john doe', 'jane doe', 'test user', 'test test',
        'fake', 'bot', 'robot',
        'lorem ipsum', 'person', 'customer',
        'testuser', 'test name', 'fake person'
    ];

    // Weighted profanity patterns - balanced scoring
    protected array $profanityPatterns = [
        // Extreme profanity - always offensive (90-100 points)
        'fuck' => 100,   // Always offensive
        'fucker' => 100, // Always offensive
        'shit' => 90,    // Often offensive
        'cock' => 90,    // Usually offensive
        'pussy' => 100,  // Always offensive
        'cunt' => 100,   // Always offensive
        'whore' => 90,   // Often offensive
        'slut' => 90,    // Often offensive
        'bitch' => 80,   // Could be legitimate in some contexts
        'dick' => 70,    // Could be legitimate name "Dick"
        'ass' => 70,     // Could be part of legitimate words
        'piss' => 80,    // Often offensive
        'bastard' => 70, // Could be legitimate in some contexts

        // Context-dependent words (50-60 points)
        'suck' => 60,    // Only offensive at start of name
        'blow' => 60,    // Could be legitimate surname
        'damn' => 50,    // Mild profanity
        'hell' => 50,    // Could be in legitimate names
        'crap' => 50,    // Mild profanity

        // Medical terms - usually not offensive in names (30 points)
        'penis' => 30,
        'vagina' => 30,
    ];

    /**
     * Analyze name for risk indicators
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    public function handle(string $name): array
    {
        $score = 0;
        $reasons = [];
        $features = [];

        $name = trim($name);
        $nameLower = strtolower($name);

        if (empty($name)) {
            return [
                'score' => 100,
                'reasons' => ['Empty name'],
                'features' => ['name_valid' => false]
            ];
        }

        // Check for repeated tokens (e.g., "Test Test", "John John")
        $tokens = explode(' ', $nameLower);
        if (count($tokens) >= 2) {
            $uniqueTokens = array_unique($tokens);
            if (count($uniqueTokens) < count($tokens)) {
                $score += 30;
                $reasons[] = 'Repeated name tokens';
                $features['repeated_tokens'] = true;
            }
        }

        // Check for single letter names
        if (strlen(str_replace(' ', '', $name)) <= 2) {
            $score += 40;
            $reasons[] = 'Single letter or very short name';
            $features['single_letter'] = true;
        }

        // Check for test names - these should be HIGH risk
        foreach ($this->testNames as $testName) {
            if (str_contains($nameLower, $testName)) {
                $score += 80; // Increased from 50 - test names are suspicious
                $reasons[] = 'Test name pattern detected';
                $features['test_name'] = true;
                break;
            }
        }

        // Check for profanity with weighted scoring
        $profanityScore = 0;
        $profanityFound = null;
        foreach ($this->profanityPatterns as $profanity => $weight) {
            if (str_contains($nameLower, $profanity)) {
                // Check position - profanity at start is worse
                $position = strpos($nameLower, $profanity);
                $positionMultiplier = 1.0;

                if ($position === 0) {
                    // Profanity at the very start - clearly intentional
                    $positionMultiplier = 1.5;
                } elseif ($position < 3) {
                    // Near the start
                    $positionMultiplier = 1.2;
                }

                // For context-sensitive words, check word boundaries and position
                if (in_array($profanity, ['suck', 'blow', 'ass', 'hell', 'dick', 'cock', 'damn', 'crap'])) {
                    // Check if it's a whole word
                    $beforeChar = $position > 0 ? $nameLower[$position - 1] : ' ';
                    $afterPos = $position + strlen($profanity);
                    $afterChar = $afterPos < strlen($nameLower) ? $nameLower[$afterPos] : ' ';

                    // Skip if part of a larger word
                    if (ctype_alpha($beforeChar) || ctype_alpha($afterChar)) {
                        continue; // Part of a word like Michelle, Heller, Dickerson, etc.
                    }

                    // Be very conservative about flagging - only flag obvious malicious intent
                    // Special cases for legitimate uses
                    if ($profanity === 'dick' && $position === 0) {
                        continue; // "Dick" as a first name is legitimate
                    }
                    if ($profanity === 'blow' && $position > 0) {
                        continue; // "Blow" as a surname is legitimate
                    }
                    if ($profanity === 'suck' && $position > 0) {
                        continue; // Only flag "suck" at the beginning if it's clearly malicious
                    }
                    if ($profanity === 'hell' && $position > 0) {
                        continue; // "Hell" could be part of legitimate names
                    }
                    if ($profanity === 'ass' && $position > 0) {
                        continue; // "Ass" could be part of legitimate words
                    }

                    $currentScore = $weight * $positionMultiplier;
                } else {
                    $currentScore = $weight * $positionMultiplier;
                }

                if ($currentScore > $profanityScore) {
                    $profanityScore = $currentScore;
                    $profanityFound = $profanity;
                }
            }
        }

        if ($profanityScore > 0) {
            // Profanity should be flagged appropriately
            $actualScore = min(100, $profanityScore);
            $score += $actualScore;
            $features['profanity'] = true;

            if ($profanityScore >= 90) {
                $reasons[] = 'Extreme profanity in name';
            } elseif ($profanityScore >= 70) {
                $reasons[] = 'Severe profanity in name';
            } elseif ($profanityScore >= 50) {
                $reasons[] = 'Profane language detected';
            } else {
                $reasons[] = 'Questionable language in name';
            }
        }

        // Check for emoji or special symbols
        if ($this->containsEmoji($name) || $this->hasExcessiveSpecialChars($name)) {
            $score += 35;
            $reasons[] = 'Emoji or excessive special characters in name';
            $features['special_chars'] = true;
        }

        // Check for all caps
        if ($name === strtoupper($name) && strlen($name) > 3) {
            $score += 15;
            $reasons[] = 'All caps name';
            $features['all_caps'] = true;
        }

        // Check for numeric characters
        if (preg_match('/[0-9]/', $name)) {
            $score += 25;
            $reasons[] = 'Numbers in name';
            $features['has_numbers'] = true;
        }

        // Check for gibberish
        if ($this->isGibberish($name)) {
            $score += 30;
            $reasons[] = 'Gibberish name';
            $features['gibberish'] = true;
        }

        // Check name structure
        $nameStructure = $this->analyzeNameStructure($name);
        $features = array_merge($features, $nameStructure);

        if (!$nameStructure['has_first_last']) {
            $score += 10;
            $reasons[] = 'Single name only';
        }

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
            'features' => $features
        ];
    }

    /**
     * Check if string contains emoji
     */
    protected function containsEmoji(string $text): bool
    {
        return preg_match('/[\x{1F000}-\x{1F9FF}]|[\x{2600}-\x{27BF}]/u', $text) === 1;
    }

    /**
     * Check for excessive special characters
     */
    protected function hasExcessiveSpecialChars(string $text): bool
    {
        $specialCount = preg_match_all('/[^a-zA-Z0-9\s\-\']/', $text);
        return $specialCount > 2 || ($specialCount > 0 && strlen($text) < 5);
    }

    /**
     * Check if name appears to be gibberish
     */
    protected function isGibberish(string $text): bool
    {
        // Remove spaces and special chars
        $text = preg_replace('/[^a-zA-Z]/', '', $text);

        if (strlen($text) < 4) {
            return false;
        }

        // Check for too many consonants in a row
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/i', $text)) {
            return true;
        }

        // Check for lack of vowels
        $vowelCount = preg_match_all('/[aeiou]/i', $text);
        $consonantCount = preg_match_all('/[bcdfghjklmnpqrstvwxyz]/i', $text);

        if ($consonantCount > 0 && $vowelCount / $consonantCount < 0.15) {
            return true;
        }

        return false;
    }

    /**
     * Analyze name structure
     */
    protected function analyzeNameStructure(string $name): array
    {
        $parts = explode(' ', trim($name));

        return [
            'name_parts' => count($parts),
            'has_first_last' => count($parts) >= 2,
            'name_length' => strlen($name),
            'avg_part_length' => count($parts) > 0 ? array_sum(array_map('strlen', $parts)) / count($parts) : 0
        ];
    }
}