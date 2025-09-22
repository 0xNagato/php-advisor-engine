<?php

namespace App\Console\Commands;

use App\Actions\Risk\ScoreBookingSuspicion;
use Illuminate\Console\Command;

class TestRiskScoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-risk-scoring {--thresholds : Show current threshold settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test risk scoring functionality with visual icons and detailed analysis to verify false positive fixes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('thresholds')) {
            $this->showThresholds();
            return;
        }

        $this->showHelp();
        $this->testSampleBookings();
    }

    protected function showHelp()
    {
        $this->info('🔍 Risk Scoring Test Command');
        $this->info('=============================');
        $this->info('Tests the risk scoring system with sample bookings to verify false positive fixes.');
        $this->info('');
        $this->info('📊 Risk Level Icons:');
        $this->info('  🟢 LOW (0-34): Proceed normally');
        $this->info('  🟡 MEDIUM (35-74): Require manual review');
        $this->info('  🔴 HIGH (75+): Likely fraudulent');
        $this->info('');
        $this->info('📋 Options:');
        $this->info('  --thresholds  Show current threshold settings');
        $this->info('  --help        Show help (auto-handled by Laravel)');
        $this->info('');
        $this->info('💡 Use --thresholds to see scoring thresholds and risk levels.');
        $this->newLine();
    }

    protected function showThresholds()
    {
        $this->info('Current Risk Scoring Thresholds:');
        $this->table(
            ['Component', 'Threshold', 'Score', 'Description'],
            [
                ['IP Extreme Volume', '60+', '100', 'Maximum penalty - extreme IP automation'],
                ['IP Burst (5min)', '5+', '30', 'IP burst activity (less reliable)'],
                ['IP Volume (hour)', '> 40', '60', 'Extreme IP volume'],
                ['IP Volume (hour)', '> 25', '30', 'High IP volume'],
                ['IP Volume (hour)', '> 15', '15', 'Elevated IP activity'],
                ['IP Volume (hour)', '> 3', '0', 'Normal concierge activity'],
                ['Device Extreme Volume', '50+', '100', 'Maximum penalty - extreme device automation'],
                ['Device Burst (5min)', '4+', '40', 'Device burst activity'],
                ['Device Volume (hour)', '> 30', '80', 'Extreme device volume'],
                ['Device Volume (hour)', '> 25', '60', 'Very high device activity'],
                ['Device Volume (hour)', '> 15', '30', 'High device activity'],
                ['Device Volume (hour)', '> 10', '15', 'Elevated device activity'],
                ['Device Volume (hour)', '> 3', '0', 'Normal concierge activity'],
                ['Test Names', 'Contains "test"', '50-80', 'Highly suspicious'],
                ['Profanity (extreme)', 'Contains "fuck", "cunt"', '100', 'Always offensive'],
                ['Profanity (severe)', 'Contains "shit", "cock"', '90', 'Usually offensive'],
                ['Invalid MX Records', 'No valid email domain', '35', 'Suspicious email'],
                ['🔴 Risk Score', '75+', 'HIGH', 'Requires manual review'],
                ['🟡 Risk Score', '35-74', 'MEDIUM', 'Auto-flags for review'],
                ['🟢 Risk Score', '0-34', 'LOW', 'Proceeds normally'],
            ]
        );
    }

    /**
     * Get risk level icon
     */
    protected function getRiskIcon(int $score): string
    {
        if ($score >= 80) {
            return '🔴'; // Red circle for HIGH
        } elseif ($score >= 40) {
            return '🟡'; // Yellow circle for MEDIUM
        } else {
            return '🟢'; // Green circle for LOW
        }
    }

    /**
     * Get risk level with icon
     */
    protected function formatRiskLevel(int $score): string
    {
        $icon = $this->getRiskIcon($score);
        $level = $score >= 80 ? 'HIGH' : ($score >= 40 ? 'MEDIUM' : 'LOW');
        return "{$icon} {$level} ({$score}/100)";
    }

    protected function testSampleBookings()
    {
        // Sample bookings that were previously flagged as high risk
        $testCases = [
            [
                'name' => 'Andrew Weir',
                'email' => 'andru.weir@gmail.com',
                'phone' => '+1234567890',
                'ip' => '127.0.0.1', // localhost
                'notes' => 'Testing the booking system',
                'venue_region' => 'miami',
                'expected' => 'should_not_flag'
            ],
            [
                'name' => 'Val Diaz',
                'email' => 'blockchainproductagency@gmail.com',
                'phone' => '+1234567890',
                'ip' => '104.16.0.1', // Cloudflare (datacenter)
                'notes' => 'Business dinner',
                'venue_region' => 'miami',
                'expected' => 'should_not_flag'
            ],
            [
                'name' => 'Paulo Althoff',
                'email' => 'pauloalthoff@gpafactoring.com.br',
                'phone' => '+5511987654321',
                'ip' => '35.0.0.1', // Google Cloud (datacenter)
                'notes' => 'Client meeting',
                'venue_region' => 'ibiza',
                'expected' => 'should_not_flag'
            ],
            [
                'name' => 'Michael Culhane',
                'email' => 'amelia.nunez@1hotels.com',
                'phone' => '+13055551234',
                'ip' => '52.0.0.1', // AWS (datacenter)
                'notes' => 'Team celebration',
                'venue_region' => 'miami',
                'expected' => 'should_not_flag'
            ],
            [
                'name' => 'Shaz Peksos',
                'email' => 'prima@primavip.co',
                'phone' => '+34612345678',
                'ip' => '34.0.0.1', // Google Cloud (datacenter)
                'notes' => 'VIP booking',
                'venue_region' => 'ibiza',
                'expected' => 'should_not_flag'
            ],
            // Obviously problematic bookings for comparison
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+1234567890',
                'ip' => '127.0.0.1',
                'notes' => 'Test booking',
                'venue_region' => 'miami',
                'expected' => 'should_flag'
            ],
            [
                'name' => 'Fuck You',
                'email' => 'bad@example.com',
                'phone' => '+1234567890',
                'ip' => '127.0.0.1',
                'notes' => 'Bad booking',
                'venue_region' => 'miami',
                'expected' => 'should_flag'
            ]
        ];

        $this->info('Testing Risk Scoring Fixes');
        $this->info('==========================');

        $results = [];

        foreach ($testCases as $i => $testCase) {
            $this->info("Test Case " . ($i + 1) . ": {$testCase['name']} ({$testCase['email']})");
            $this->info(str_repeat('-', 50));

            try {
                // Test the full risk scoring system including AI analysis
                $result = ScoreBookingSuspicion::run(
                    $testCase['email'],
                    $testCase['phone'],
                    $testCase['name'],
                    $testCase['ip'],
                    'Mozilla/5.0 (Test Browser)',
                    $testCase['notes']
                );

                $totalScore = $result['score'];
                $riskLevel = $totalScore >= 80 ? 'HIGH' : ($totalScore >= 40 ? 'MEDIUM' : 'LOW');
                $riskIcon = $this->getRiskIcon($totalScore);

                // Get breakdown from the full analysis
                $features = $result['features'];
                $breakdown = $features['breakdown'] ?? [];

                // Show breakdown including AI analysis
                $tableData = [];
                if (isset($breakdown['email'])) {
                    $tableData[] = ['Email', $breakdown['email']['score'], implode(', ', $breakdown['email']['reasons'] ?? [])];
                }
                if (isset($breakdown['name'])) {
                    $tableData[] = ['Name', $breakdown['name']['score'], implode(', ', $breakdown['name']['reasons'] ?? [])];
                }
                if (isset($breakdown['ip'])) {
                    $tableData[] = ['IP', $breakdown['ip']['score'], implode(', ', $breakdown['ip']['reasons'] ?? [])];
                }
                if (isset($breakdown['phone'])) {
                    $tableData[] = ['Phone', $breakdown['phone']['score'], implode(', ', $breakdown['phone']['reasons'] ?? [])];
                }
                if (isset($breakdown['behavioral'])) {
                    $tableData[] = ['Behavioral', $breakdown['behavioral']['score'], implode(', ', $breakdown['behavioral']['reasons'] ?? [])];
                }

                // Add AI analysis row
                $aiScore = $features['llm_used'] ? 'AI Applied' : 'Not Used';
                $aiReasons = $features['llm_used'] ? 'AI enhanced scoring' : 'Rules only';
                $tableData[] = ['🤖 AI Analysis', $aiScore, $aiReasons];

                $this->table(['Component', 'Score/Details', 'Reasons'], $tableData);

                $this->info("Total Score: {$totalScore}/100");
                $this->info("Risk Level: {$this->formatRiskLevel($totalScore)}");

                // Show all reasons from the full analysis
                if (!empty($result['reasons'])) {
                    $this->info("All Risk Reasons:");
                    foreach ($result['reasons'] as $reason) {
                        $this->line("  - {$reason}");
                    }
                }

                // Show AI response if available
                if ($features['llm_used'] && isset($features['llm_response'])) {
                    $this->info("AI Response: " . substr($features['llm_response'], 0, 200) . '...');
                }

                $isFalsePositive = ($totalScore >= 40 && $testCase['expected'] === 'should_not_flag');
                $status = $isFalsePositive ? '❌ FALSE POSITIVE' : '✅ CORRECT';
                $this->info("Result: {$status}");

                $results[] = [
                    'name' => $testCase['name'],
                    'score' => $totalScore,
                    'risk_level' => $riskLevel,
                    'risk_display' => $this->formatRiskLevel($totalScore),
                    'expected' => $testCase['expected'],
                    'result' => $isFalsePositive ? 'FALSE_POSITIVE' : 'CORRECT',
                    'ai_used' => $features['llm_used'] ?? false
                ];

            } catch (\Exception $e) {
                $this->error("Error: {$e->getMessage()}");
            }

            $this->newLine();
        }

        // Show summary
        $this->info('Summary:');
        $this->info('=====================================');

        // Risk Level Distribution Table
        $lowCount = collect($results)->where('risk_level', 'LOW')->count();
        $mediumCount = collect($results)->where('risk_level', 'MEDIUM')->count();
        $highCount = collect($results)->where('risk_level', 'HIGH')->count();

        $this->table(
            ['Risk Level', 'Count', 'Percentage', 'Description'],
            [
                ['🟢 LOW (0-39)', $lowCount, round(($lowCount / count($results)) * 100, 1) . '%', 'Proceed normally'],
                ['🟡 MEDIUM (40-79)', $mediumCount, round(($mediumCount / count($results)) * 100, 1) . '%', 'Require manual review'],
                ['🔴 HIGH (80+)', $highCount, round(($highCount / count($results)) * 100, 1) . '%', 'Likely fraudulent'],
            ]
        );

        $falsePositives = collect($results)->where('result', 'FALSE_POSITIVE')->count();
        $this->info("False Positives: {$falsePositives}/" . count($results));

        // Results breakdown
        $correctCount = collect($results)->where('result', 'CORRECT')->count();
        $totalCount = count($results);

        if ($falsePositives === 0) {
            $this->info('🎉 All test cases passed! Risk scoring is working correctly.');
        } else {
            $this->warn("⚠️ {$falsePositives} false positives detected. May need further tuning.");
        }

        $this->info("✅ Correct Results: {$correctCount}/{$totalCount} (" . round(($correctCount / $totalCount) * 100, 1) . '%)');

        // Show detailed results summary
        if ($totalCount > 0) {
            $this->info('');
            $this->info('📊 Detailed Results:');
            $this->table(
                ['Name', 'Score', 'Risk Level', 'Expected', 'Result'],
                collect($results)->map(function ($result) {
                    $resultIcon = $result['result'] === 'CORRECT' ? '✅' : '❌';
                    return [
                        $result['name'],
                        $result['score'] . '/100',
                        $this->getRiskIcon($result['score']) . ' ' . $result['risk_level'],
                        $result['expected'] === 'should_not_flag' ? 'Should Pass' : 'Should Flag',
                        $resultIcon . ' ' . $result['result']
                    ];
                })->toArray()
            );
        }
    }
}
