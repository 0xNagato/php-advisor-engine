<?php

use App\Actions\Risk\Analyzers\AnalyzeEmailRisk;
use App\Actions\Risk\Analyzers\AnalyzeIPRisk;
use App\Actions\Risk\Analyzers\AnalyzeNameRisk;
use App\Actions\Risk\Analyzers\AnalyzePhoneRisk;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('app.risk_screening_enabled', true);
    Config::set('app.ai_screening_enabled', false);
    Config::set('app.ai_screening_threshold_soft', 30);
    Config::set('app.ai_screening_threshold_hard', 70);
});

describe('Email Risk Analysis', function () {
    it('detects disposable email domains', function () {
        $result = AnalyzeEmailRisk::run('test@mailinator.com');

        expect($result['score'])->toBeGreaterThanOrEqual(40)
            ->and($result['reasons'])->toContain('Disposable email domain')
            ->and($result['features']['disposable_email'])->toBeTrue();
    });

    it('detects no-reply patterns', function () {
        $result = AnalyzeEmailRisk::run('noreply@example.com');

        expect($result['score'])->toBeGreaterThanOrEqual(25)
            ->and($result['reasons'])->toContain('No-reply email pattern');
    });

    it('detects gibberish email usernames', function () {
        $result = AnalyzeEmailRisk::run('qwrtypsdflk@example.com');

        expect($result['score'])->toBeGreaterThanOrEqual(20)
            ->and($result['reasons'])->toContain('Gibberish email username');
    });

    it('accepts valid email addresses', function () {
        $result = AnalyzeEmailRisk::run('john.smith@gmail.com');

        expect($result['score'])->toBeLessThan(30);
    });
});

describe('Phone Risk Analysis', function () {
    it('detects repeating digit patterns', function () {
        $result = AnalyzePhoneRisk::run('+19898989898');

        expect($result['score'])->toBeGreaterThanOrEqual(40)
            ->and($result['reasons'])->toContain('Repeating digit pattern in phone');
    });

    it('detects sequential digits', function () {
        $result = AnalyzePhoneRisk::run('1234567890');

        expect($result['score'])->toBeGreaterThanOrEqual(30)
            ->and($result['reasons'])->toContain('Sequential digits in phone');
    });

    it('detects test phone numbers', function () {
        $result = AnalyzePhoneRisk::run('5555555555');

        expect($result['score'])->toBeGreaterThanOrEqual(60)
            ->and($result['reasons'])->toContain('Test phone number');
    });

    it('validates NANP format', function () {
        $result = AnalyzePhoneRisk::run('+10123456789'); // Invalid area code starting with 0

        expect($result['score'])->toBeGreaterThanOrEqual(25)
            ->and($result['reasons'])->toContain('Invalid NANP area code');
    });

    it('accepts valid phone numbers', function () {
        $result = AnalyzePhoneRisk::run('+12125551234');

        expect($result['score'])->toBeLessThan(30);
    });
});

describe('Name Risk Analysis', function () {
    it('detects repeated name tokens', function () {
        $result = AnalyzeNameRisk::run('Test Test');

        expect($result['score'])->toBeGreaterThanOrEqual(30)
            ->and($result['reasons'])->toContain('Repeated name tokens');
    });

    it('detects test names', function () {
        $result = AnalyzeNameRisk::run('John Doe');

        expect($result['score'])->toBeGreaterThanOrEqual(50)
            ->and($result['reasons'])->toContain('Test name pattern');
    });

    it('detects single letter names', function () {
        $result = AnalyzeNameRisk::run('A B');

        expect($result['score'])->toBeGreaterThanOrEqual(40)
            ->and($result['reasons'])->toContain('Single letter or very short name');
    });

    it('accepts valid names', function () {
        $result = AnalyzeNameRisk::run('Elizabeth Anderson');

        expect($result['score'])->toBeLessThan(30);
    });
});

describe('LLM Risk Evaluation', function () {
    it('falls back to rule-based scoring when API key not configured', function () {
        Config::set('services.openai.key', null);
        Config::set('app.ai_screening_enabled', true);

        $features = [
            'disposable_email' => true,
            'datacenter_ip' => true,
            'test_number' => true,
        ];

        $result = \App\Actions\Risk\EvaluateWithLLM::run($features);

        expect($result['risk_score'])->toBeGreaterThanOrEqual(50)
            ->and($result['reasons'])->toBeArray()
            ->and($result['reasons'])->not->toBeEmpty();
    });

    it('uses fallback scoring for high-risk indicators', function () {
        Config::set('services.openai.key', null);

        $features = [
            'disposable_email' => true,
            'test_number' => true,
            'velocity_burst' => true,
            'tor_exit' => true,
        ];

        $result = \App\Actions\Risk\EvaluateWithLLM::run($features);

        expect($result['risk_score'])->toBeGreaterThanOrEqual(70)
            ->and($result['reasons'])->toContain('Disposable email service detected')
            ->and($result['reasons'])->toContain('Test phone number pattern')
            ->and($result['reasons'])->toContain('Rapid submission velocity')
            ->and($result['reasons'])->toContain('Tor network usage');
    });

    it('uses fallback scoring for medium-risk indicators', function () {
        Config::set('services.openai.key', null);

        $features = [
            'datacenter_ip' => true,
            'gibberish_email' => true,
            'test_name' => true,
        ];

        $result = \App\Actions\Risk\EvaluateWithLLM::run($features);

        expect($result['risk_score'])->toBeBetween(30, 70)
            ->and($result['reasons'])->toContain('Datacenter IP address')
            ->and($result['reasons'])->toContain('Suspicious email pattern')
            ->and($result['reasons'])->toContain('Test name detected');
    });

    it('limits reasons to 5 maximum', function () {
        Config::set('services.openai.key', null);

        $features = [
            'disposable_email' => true,
            'test_number' => true,
            'velocity_burst' => true,
            'tor_exit' => true,
            'datacenter_ip' => true,
            'gibberish_email' => true,
            'test_name' => true,
            'venue_hopping' => true,
            'repeating_pattern' => true,
            'geo_mismatch' => true,
        ];

        $result = \App\Actions\Risk\EvaluateWithLLM::run($features);

        expect($result['reasons'])->toHaveCount(5);
    });

    it('includes PII in prompt for better context', function () {
        $features = [
            'email' => 'john@test.com',
            'phone' => '+1234567890',
            'name' => 'John Doe',
            'ip' => '192.168.1.1',
            'notes' => 'Special request for window seat',
            'disposable_email' => true,
            'datacenter_ip' => true,
        ];

        // Use reflection to test the protected method
        $evaluator = new \App\Actions\Risk\EvaluateWithLLM;
        $reflection = new \ReflectionClass($evaluator);
        $method = $reflection->getMethod('buildUserPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($evaluator, $features);

        expect($prompt)->toContain('john@test.com')
            ->and($prompt)->toContain('+1234567890')
            ->and($prompt)->toContain('John Doe')
            ->and($prompt)->toContain('192.168.1.1')
            ->and($prompt)->toContain('Special request for window seat')
            ->and($prompt)->toContain('Using disposable email domain')
            ->and($prompt)->toContain('IP from datacenter/hosting provider');
    });
});

describe('IP Risk Analysis', function () {
    it('detects datacenter IPs', function () {
        $result = AnalyzeIPRisk::run('104.16.1.1'); // Cloudflare IP

        expect($result['score'])->toBeGreaterThanOrEqual(15) // Reduced from 30 - many legitimate users use VPNs
            ->and($result['reasons'])->toContain('Datacenter/VPN IP address');
    });

    it('does not penalize private IP addresses', function () {
        $result = AnalyzeIPRisk::run('192.168.1.1');

        // Private IPs are normal for local testing, so we don't penalize them
        expect($result['score'])->toBe(0)
            ->and($result['features']['private_ip'])->toBeTrue()
            ->and($result['reasons'])->not->toContain('Private IP address');
    });

    it('accepts regular IPs', function () {
        $result = AnalyzeIPRisk::run('8.8.8.8');

        expect($result['score'])->toBeLessThan(50);
    });
});
