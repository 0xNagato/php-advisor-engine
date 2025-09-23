<?php

namespace App\Actions\Risk\Analyzers;

use Lorisleiva\Actions\Concerns\AsAction;

class AnalyzePhoneRisk
{
    use AsAction;

    /**
     * Analyze phone number for risk indicators
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    public function handle(string $phone): array
    {
        $score = 0;
        $reasons = [];
        $features = [];

        // Clean phone number
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($phone) || strlen($phone) < 10) {
            return [
                'score' => 100,
                'reasons' => ['Invalid phone number'],
                'features' => ['phone_valid' => false],
            ];
        }

        // Check E.164 format
        $features['e164_format'] = str_starts_with($phone, '+');
        if (! $features['e164_format'] && ! str_starts_with($phone, '1')) {
            $score += 10;
            $reasons[] = 'Non-standard phone format';
        }

        // Check for repeating digits (e.g., 9898989898)
        if ($this->hasRepeatingPattern($phone)) {
            $score += 40;
            $reasons[] = 'Repeating digit pattern in phone';
            $features['repeating_pattern'] = true;
        }

        // Check for sequential digits
        if ($this->hasSequentialDigits($phone)) {
            $score += 30;
            $reasons[] = 'Sequential digits in phone';
            $features['sequential_digits'] = true;
        }

        // Check for all same digits
        if ($this->hasAllSameDigits($phone)) {
            $score += 50;
            $reasons[] = 'All same digits in phone';
            $features['all_same_digits'] = true;
        }

        // Check NANP format for US/CA numbers
        if (str_starts_with($phone, '1') || str_starts_with($phone, '+1')) {
            $nanpCheck = $this->checkNANPFormat($phone);
            if (! $nanpCheck['valid']) {
                $score += 25;
                $reasons[] = $nanpCheck['reason'];
                $features['nanp_valid'] = false;
            } else {
                $features['nanp_valid'] = true;
            }
        }

        // Check for known VoIP ranges (stub implementation)
        $voipCheck = $this->checkVoIPNumber($phone);
        if ($voipCheck) {
            $score += 15;
            $reasons[] = 'VoIP phone number';
            $features['voip'] = true;
        }

        // Check for test numbers
        if ($this->isTestNumber($phone)) {
            $score += 60;
            $reasons[] = 'Test phone number';
            $features['test_number'] = true;
        }

        $features['phone_length'] = strlen($phone);
        $features['phone_country_code'] = $this->extractCountryCode($phone);

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
            'features' => $features,
        ];
    }

    /**
     * Check for repeating pattern in phone number
     */
    protected function hasRepeatingPattern(string $phone): bool
    {
        // Remove country code if present
        $phone = preg_replace('/^\+?1/', '', $phone);

        // Check for patterns like 989898, 123123, etc.
        if (strlen((string) $phone) >= 6) {
            for ($len = 2; $len <= 4; $len++) {
                $pattern = substr((string) $phone, 0, $len);
                $repeated = str_repeat($pattern, (int) (strlen((string) $phone) / $len));
                if (str_starts_with((string) $phone, $repeated) && strlen($repeated) >= strlen((string) $phone) - 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for sequential digits
     */
    protected function hasSequentialDigits(string $phone): bool
    {
        // Remove country code
        $phone = preg_replace('/^\+?1/', '', $phone);

        // Check for sequences like 123456, 987654
        $ascending = 0;
        $descending = 0;

        for ($i = 1; $i < strlen((string) $phone); $i++) {
            $diff = intval($phone[$i]) - intval($phone[$i - 1]);
            if ($diff == 1) {
                $ascending++;
                if ($ascending >= 5) {
                    return true;
                }
            } else {
                $ascending = 0;
            }

            if ($diff == -1) {
                $descending++;
                if ($descending >= 5) {
                    return true;
                }
            } else {
                $descending = 0;
            }
        }

        return false;
    }

    /**
     * Check for all same digits
     */
    protected function hasAllSameDigits(string $phone): bool
    {
        // Remove country code
        $phone = preg_replace('/^\+?1/', '', $phone);

        // Check if all digits are the same
        $firstDigit = $phone[0];
        for ($i = 1; $i < strlen((string) $phone); $i++) {
            if ($phone[$i] !== $firstDigit) {
                return false;
            }
        }

        return strlen((string) $phone) >= 10;
    }

    /**
     * Check NANP format for US/CA numbers
     */
    protected function checkNANPFormat(string $phone): array
    {
        // Remove country code
        $phone = preg_replace('/^\+?1/', '', $phone);

        if (strlen((string) $phone) !== 10) {
            return ['valid' => false, 'reason' => 'Invalid NANP length'];
        }

        // NPA (area code) cannot start with 0 or 1
        if ($phone[0] == '0' || $phone[0] == '1') {
            return ['valid' => false, 'reason' => 'Invalid NANP area code'];
        }

        // NXX (exchange) cannot start with 0 or 1
        if ($phone[3] == '0' || $phone[3] == '1') {
            return ['valid' => false, 'reason' => 'Invalid NANP exchange'];
        }

        // Check for N11 codes (211, 311, 411, etc.)
        if ($phone[1] == '1' && $phone[2] == '1') {
            return ['valid' => false, 'reason' => 'N11 service code'];
        }

        return ['valid' => true];
    }

    /**
     * Check if number is likely VoIP (stub implementation)
     */
    protected function checkVoIPNumber(string $phone): bool
    {
        // In production, this would check against known VoIP ranges
        // or use a carrier lookup service
        // For now, just check some known patterns

        // Remove country code
        $phone = preg_replace('/^\+?1/', '', $phone);

        // Known VoIP area codes (partial list)
        $voipAreaCodes = ['567', '762', '445', '564'];
        $areaCode = substr((string) $phone, 0, 3);

        return in_array($areaCode, $voipAreaCodes);
    }

    /**
     * Check if it's a test number
     */
    protected function isTestNumber(string $phone): bool
    {
        $testNumbers = [
            '5555555555',
            '1234567890',
            '9876543210',
            '1111111111',
            '0000000000',
            '9999999999',
            '1231231234',
        ];

        $cleanPhone = preg_replace('/^\+?1/', '', $phone);

        return in_array($cleanPhone, $testNumbers);
    }

    /**
     * Extract country code from phone number
     */
    protected function extractCountryCode(string $phone): ?string
    {
        if (str_starts_with($phone, '+')) {
            // Common country codes
            $countryCodes = ['1', '44', '33', '49', '39', '34', '61', '86', '91', '7'];
            foreach ($countryCodes as $code) {
                if (str_starts_with($phone, '+'.$code)) {
                    return $code;
                }
            }
        }

        return null;
    }
}
