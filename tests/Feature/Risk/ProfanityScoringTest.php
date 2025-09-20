<?php

use App\Actions\Risk\Analyzers\AnalyzeEmailRisk;
use App\Actions\Risk\Analyzers\AnalyzeNameRisk;
use App\Actions\Risk\ScoreBookingSuspicion;

beforeEach(function () {
    // Disable AI screening for consistent testing
    config(['app.ai_screening_enabled' => false]);
});

describe('Profanity Detection and Scoring', function () {
    describe('Name Profanity Detection', function () {
        it('detects extreme profanity at start of name', function () {
            $result = AnalyzeNameRisk::run('Fuck You');

            expect($result['score'])->toBeGreaterThanOrEqual(100)
                ->and($result['reasons'])->toContain('Extreme profanity in name');
        });

        it('detects offensive patterns like "Suck My"', function () {
            $result = AnalyzeNameRisk::run('Suck My');

            expect($result['score'])->toBeGreaterThanOrEqual(90)
                ->and($result['reasons'])->toContain('Offensive/profane name');
        });

        it('allows Dick as a legitimate first name', function () {
            $result = AnalyzeNameRisk::run('Dick Johnson');

            expect($result['score'])->toBeLessThan(30)
                ->and($result['reasons'])->not->toContain('Extreme profanity in name')
                ->and($result['reasons'])->not->toContain('Offensive/profane name');
        });

        it('allows Blow as a legitimate surname', function () {
            $result = AnalyzeNameRisk::run('Michelle Blow');

            expect($result['score'])->toBeLessThan(30)
                ->and($result['reasons'])->not->toContain('Offensive/profane name');
        });

        it('does not flag hell in Michelle', function () {
            $result = AnalyzeNameRisk::run('Michelle Smith');

            expect($result['score'])->toBe(0)
                ->and($result['reasons'])->toBeEmpty();
        });

        it('does not flag suck in middle of surname', function () {
            $result = AnalyzeNameRisk::run('John Suckerman');

            expect($result['score'])->toBe(0)
                ->and($result['reasons'])->toBeEmpty();
        });
    });

    describe('Email Profanity Detection', function () {
        it('detects extreme profanity in email', function () {
            $result = AnalyzeEmailRisk::run('fucker@dick.com');

            expect($result['score'])->toBe(100)
                ->and($result['reasons'])->toContain('Extreme profanity in email');
        });

        it('detects multiple profanities in email', function () {
            $result = AnalyzeEmailRisk::run('shit@cunt.com');

            expect($result['score'])->toBe(100)
                ->and($result['reasons'])->toContain('Extreme profanity in email');
        });

        it('flags dick in email as suspicious but not extreme', function () {
            $result = AnalyzeEmailRisk::run('dick.johnson@gmail.com');

            expect($result['score'])->toBe(80)
                ->and($result['reasons'])->toContain('Offensive/profane email address');
        });

        it('does not flag hell in michelle email', function () {
            $result = AnalyzeEmailRisk::run('michelle@gmail.com');

            expect($result['score'])->toBe(0)
                ->and($result['features']['profane_email'] ?? false)->toBeFalse();
        });
    });

    describe('Combined Risk Scoring', function () {
        it('scores extreme profanity combinations at 100', function () {
            $result = ScoreBookingSuspicion::run(
                email: 'fucker@dick.com',
                phone: '+14165551234',
                name: 'Suck My',
                ipAddress: '8.8.8.8',
                userAgent: 'Mozilla/5.0',
                notes: '',
                booking: null
            );

            expect($result['score'])->toBe(100)
                ->and($result['reasons'])->toContain('Extreme profanity in email')
                ->and($result['reasons'])->toContain('Offensive/profane name');
        });

        it('scores legitimate names appropriately', function () {
            $result = ScoreBookingSuspicion::run(
                email: 'michelle@gmail.com',
                phone: '+14165551234',
                name: 'Michelle Blow',
                ipAddress: '8.8.8.8',
                userAgent: 'Mozilla/5.0',
                notes: '',
                booking: null
            );

            expect($result['score'])->toBeLessThan(30);
        });

        it('applies minimum score of 70 for single extreme profanity', function () {
            // Clear cache to avoid velocity issues
            Cache::flush();

            $result = ScoreBookingSuspicion::run(
                email: 'test@gmail.com',
                phone: '+14165551234',
                name: 'Fuck You',
                ipAddress: '192.168.1.100',  // Use different IP to avoid velocity
                userAgent: 'Mozilla/5.0',
                notes: '',
                booking: null
            );

            expect($result['score'])->toBeGreaterThanOrEqual(70);
        });
    });
});

describe('Velocity Scoring', function () {
    beforeEach(function () {
        // Clear cache to reset velocity counters
        Cache::flush();
    });

    it('scores extreme burst velocity at 90', function () {
        // Simulate 6 bookings within 5 minutes (burst)
        $ipAddress = '1.2.3.4';
        $cacheKey = 'ip_velocity:'.hash('sha256', $ipAddress);
        // All timestamps within last 5 minutes = burst
        $timestamps = array_fill(0, 6, now()->subMinutes(2)->timestamp);
        Cache::put($cacheKey, $timestamps, now()->addHours(2));

        $result = \App\Actions\Risk\Analyzers\AnalyzeIPRisk::run($ipAddress);

        expect($result['score'])->toBe(90)
            ->and($result['reasons'][0])->toContain('Extreme burst: 7 bookings in 5 minutes');
    });

    it('scores rapid burst at 70', function () {
        // Simulate 4 bookings within 5 minutes (rapid burst)
        $ipAddress = '1.2.3.5';
        $cacheKey = 'ip_velocity:'.hash('sha256', $ipAddress);
        // All timestamps within last 5 minutes = rapid burst
        $timestamps = array_fill(0, 4, now()->subMinutes(3)->timestamp);
        Cache::put($cacheKey, $timestamps, now()->addHours(2));

        $result = \App\Actions\Risk\Analyzers\AnalyzeIPRisk::run($ipAddress);

        expect($result['score'])->toBe(70)
            ->and($result['reasons'][0])->toContain('Rapid burst: 5 bookings in 5 minutes');
    });

    it('scores moderate velocity appropriately', function () {
        // Simulate 7 bookings spread across an hour (normal for concierge)
        $ipAddress = '1.2.3.6';
        $cacheKey = 'ip_velocity:'.hash('sha256', $ipAddress);
        // Spread timestamps across the hour to avoid burst detection
        $timestamps = [
            now()->subMinutes(50)->timestamp,
            now()->subMinutes(40)->timestamp,
            now()->subMinutes(30)->timestamp,
            now()->subMinutes(20)->timestamp,
            now()->subMinutes(15)->timestamp,
            now()->subMinutes(10)->timestamp,
            now()->subMinutes(6)->timestamp, // Keep one outside 5min window
        ];
        Cache::put($cacheKey, $timestamps, now()->addHours(2));

        $result = \App\Actions\Risk\Analyzers\AnalyzeIPRisk::run($ipAddress);

        expect($result['score'])->toBe(10)
            ->and($result['reasons'][0])->toContain('Multiple bookings: 8 from same IP in last hour');
    });

    it('scores high volume spread across hour at 30', function () {
        // Simulate 12 bookings spread across an hour (busy concierge)
        $ipAddress = '1.2.3.7';
        $cacheKey = 'ip_velocity:'.hash('sha256', $ipAddress);
        // Spread timestamps to avoid burst detection
        $timestamps = [];
        for ($i = 0; $i < 12; $i++) {
            $timestamps[] = now()->subMinutes(55 - ($i * 4))->timestamp;
        }
        Cache::put($cacheKey, $timestamps, now()->addHours(2));

        $result = \App\Actions\Risk\Analyzers\AnalyzeIPRisk::run($ipAddress);

        expect($result['score'])->toBe(30)
            ->and($result['reasons'][0])->toContain('High volume: 13 bookings in last hour');
    });

    it('scores extreme device velocity at 80', function () {
        $device = 'test-device-id';
        $cacheKey = 'device_velocity:'.$device;
        $timestamps = array_fill(0, 25, now()->timestamp);
        Cache::put($cacheKey, $timestamps, now()->addHours(2));

        // Create a real booking with device
        $booking = new \App\Models\Booking;
        $booking->device = $device;

        $result = \App\Actions\Risk\Analyzers\AnalyzeBehavioralSignals::run(
            email: 'test@test.com',
            phone: '+14165551234',
            ipAddress: null,
            notes: '',
            booking: $booking
        );

        expect($result['score'])->toBeGreaterThanOrEqual(80)
            ->and($result['reasons'][0])->toContain('Extreme device abuse');
    });
});

describe('Risk Metadata Storage', function () {
    it('stores breakdown in risk metadata', function () {
        // Ensure risk screening is enabled for this test
        config(['app.risk_screening_enabled' => true]);

        // Create a venue and schedule template first
        $venue = \App\Models\Venue::factory()->create();
        $scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
            'venue_id' => $venue->id,
        ]);

        $booking = \App\Models\Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_email' => 'fuck@shit.com',
            'guest_first_name' => 'Fuck',
            'guest_last_name' => 'You',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test/1.0',
            'booking_at' => now()->addDays(2),
            'is_prime' => false,
        ]);

        \App\Actions\Risk\ProcessBookingRisk::run($booking);

        $booking->refresh();

        expect($booking->risk_metadata)->not->toBeNull()
            ->and($booking->risk_metadata->breakdown)->not->toBeNull()
            ->and($booking->risk_metadata->totalScore)->toBe($booking->risk_score)
            ->and($booking->risk_metadata->breakdown)->toHaveKeys(['email', 'phone', 'name', 'ip', 'behavioral']);
    });

    it('calculates risk level correctly', function () {
        $metadata = new \App\Data\RiskMetadata(
            totalScore: 85
        );

        expect($metadata->getRiskLevel())->toBe('High Risk')
            ->and($metadata->getRiskLevelColor())->toBe('danger');

        $metadata2 = new \App\Data\RiskMetadata(
            totalScore: 45
        );

        expect($metadata2->getRiskLevel())->toBe('Medium Risk')
            ->and($metadata2->getRiskLevelColor())->toBe('warning');

        $metadata3 = new \App\Data\RiskMetadata(
            totalScore: 15
        );

        expect($metadata3->getRiskLevel())->toBe('Low Risk')
            ->and($metadata3->getRiskLevelColor())->toBe('success');
    });
});

describe('Weighted Profanity Scoring', function () {
    it('scores extreme profanity higher than mild profanity', function () {
        // Extreme profanity (fuck)
        $extremeResult = AnalyzeNameRisk::run('Fuck You');

        // Mild profanity (damn)
        $mildResult = AnalyzeNameRisk::run('Damn You');

        expect($extremeResult['score'])->toBeGreaterThan($mildResult['score'])
            ->and($extremeResult['score'])->toBeGreaterThanOrEqual(100)
            ->and($mildResult['score'])->toBeLessThanOrEqual(60);
    });

    it('detects position-sensitive profanity correctly', function () {
        // "suck" at start is offensive
        $offensiveResult = AnalyzeNameRisk::run('Suck It');

        // "suck" in middle of surname is fine
        $legitimateResult = AnalyzeNameRisk::run('John Suckerman');

        expect($offensiveResult['score'])->toBeGreaterThanOrEqual(60)
            ->and($offensiveResult['reasons'])->toContain('Offensive/profane name')
            ->and($legitimateResult['score'])->toBe(0);
    });

    it('handles multiple profanities correctly', function () {
        $result = AnalyzeEmailRisk::run('shit@fuck.com');

        expect($result['score'])->toBe(100)
            ->and($result['reasons'])->toContain('Extreme profanity in email');
    });
});

describe('Word Boundary Detection', function () {
    it('does not flag legitimate words containing profanity substrings', function () {
        // Test names
        expect(AnalyzeNameRisk::run('Michelle Smith')['score'])->toBe(0);
        expect(AnalyzeNameRisk::run('Dick Johnson')['score'])->toBeLessThan(30);
        expect(AnalyzeNameRisk::run('Michael Blow')['score'])->toBeLessThan(30);
        expect(AnalyzeNameRisk::run('Ashley Cockburn')['score'])->toBeLessThan(30);

        // Test emails
        expect(AnalyzeEmailRisk::run('michelle@gmail.com')['score'])->toBe(0);
        expect(AnalyzeEmailRisk::run('ashleycockburn@gmail.com')['score'])->toBe(0);
    });

    it('correctly identifies actual profanity with word boundaries', function () {
        // Test names with actual profanity
        expect(AnalyzeNameRisk::run('Hell Yeah')['score'])->toBeGreaterThan(30);
        expect(AnalyzeNameRisk::run('Ass Hole')['score'])->toBeGreaterThan(50);

        // Test emails with actual profanity
        expect(AnalyzeEmailRisk::run('hell.yeah@gmail.com')['score'])->toBeGreaterThan(30);
        expect(AnalyzeEmailRisk::run('ass@hole.com')['score'])->toBeGreaterThan(50);
    });
});

describe('Extreme Case Handling', function () {
    it('gives maximum score for multiple extreme red flags', function () {
        Cache::flush();

        // Multiple extreme red flags should get maximum score
        $result = ScoreBookingSuspicion::run(
            email: 'fucker@shit.com',
            phone: '+14165551234',
            name: 'Fuck You',
            ipAddress: '8.8.8.8',
            userAgent: 'Mozilla/5.0',
            notes: '',
            booking: null
        );

        expect($result['score'])->toBe(100);
    });

    it('applies minimum score of 70 for extreme profanity even with low other scores', function () {
        Cache::flush();

        $result = ScoreBookingSuspicion::run(
            email: 'normal@gmail.com',
            phone: '+14165551234',
            name: 'Fuck You',
            ipAddress: '192.168.1.100',
            userAgent: 'Mozilla/5.0',
            notes: '',
            booking: null
        );

        expect($result['score'])->toBeGreaterThanOrEqual(70)
            ->and($result['reasons'])->toContain('Extreme profanity in name');
    });
});

describe('Email-specific Profanity Detection', function () {
    it('scores dick in email as suspicious but not extreme', function () {
        $result = AnalyzeEmailRisk::run('dick.johnson@gmail.com');

        expect($result['score'])->toBe(80)
            ->and($result['reasons'])->toContain('Offensive/profane email address');
    });

    it('detects profanity in domain as well as local part', function () {
        $result = AnalyzeEmailRisk::run('test@fuck.com');

        expect($result['score'])->toBe(100)
            ->and($result['reasons'])->toContain('Extreme profanity in email');
    });
});
