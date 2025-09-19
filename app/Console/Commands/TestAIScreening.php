<?php

namespace App\Console\Commands;

use App\Actions\Risk\EvaluateWithLLM;
use App\Actions\Risk\ScoreBookingSuspicion;
use App\Actions\Risk\ProcessBookingRisk;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAIScreening extends Command
{
    protected $signature = 'test:ai-screening {--booking-id=}';
    protected $description = 'Test AI screening functionality';

    public function handle()
    {
        // Enable AI screening
        config(['app.ai_screening_enabled' => true]);

        $this->info('=== AI Screening Test ===');

        // Test configuration
        $this->info('Configuration:');
        $this->line('  AI Enabled: ' . (config('app.ai_screening_enabled') ? 'YES' : 'NO'));
        $this->line('  OpenAI API Key: ' . (config('services.openai.key') ? 'SET' : 'NOT SET'));

        // Test direct API call
        $this->info("\nTesting direct OpenAI API call...");
        $this->testDirectAPI();

        // Test EvaluateWithLLM
        $this->info("\nTesting EvaluateWithLLM action...");
        $this->testEvaluateWithLLM();

        // Test ScoreBookingSuspicion
        $this->info("\nTesting ScoreBookingSuspicion...");
        $this->testScoreBookingSuspicion();

        // Test on actual booking if ID provided
        if ($bookingId = $this->option('booking-id')) {
            $this->info("\nTesting on Booking #{$bookingId}...");
            $this->testOnBooking($bookingId);
        }

        return Command::SUCCESS;
    }

    private function testDirectAPI()
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            $this->error('No API key configured!');
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "test successful" in JSON: {"status": "..."}']
                    ],
                    'temperature' => 0,
                    'max_tokens' => 20,
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->successful()) {
                $this->info('✅ Direct API call successful');
                $content = $response->json()['choices'][0]['message']['content'] ?? '';
                $this->line('Response: ' . $content);
            } else {
                $this->error('❌ API call failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
        }
    }

    private function testEvaluateWithLLM()
    {
        $features = [
            'email' => 'loose.goose@example.com',
            'phone' => '+14155551234',
            'name' => 'Loose Goose',
            'ip' => '192.168.1.1',
        ];

        try {
            // First check what config key it's actually using
            $reflection = new \ReflectionClass(EvaluateWithLLM::class);
            $method = $reflection->getMethod('callLLMAPI');
            $method->setAccessible(true);

            // Get the source code to check
            $filename = $reflection->getFileName();
            $lines = file($filename);
            $this->line('Checking line 51: ' . trim($lines[50])); // Line 51 is index 50

            $result = EvaluateWithLLM::run($features);

            $this->info('✅ EvaluateWithLLM executed');
            $this->line('  Risk Score: ' . $result['risk_score']);
            $this->line('  Confidence: ' . $result['confidence']);
            $this->line('  Analysis: ' . $result['analysis']);
            $this->line('  Reasons: ' . implode(', ', $result['reasons']));
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->line('  File: ' . $e->getFile());
            $this->line('  Line: ' . $e->getLine());
        }
    }

    private function testScoreBookingSuspicion()
    {
        $result = ScoreBookingSuspicion::run(
            'suspicious@example.com',
            '+14155551234',
            'Suspicious Name',
            '192.168.1.1',
            'Mozilla/5.0',
            null,
            null
        );

        $this->info('ScoreBookingSuspicion result:');
        $this->line('  Score: ' . $result['score']);
        $this->line('  LLM Used: ' . ($result['features']['llm_used'] ?? false ? 'YES' : 'NO'));

        if (isset($result['features']['llm_response'])) {
            $ai = json_decode($result['features']['llm_response'], true);
            $this->line('  AI Score: ' . ($ai['risk_score'] ?? 'N/A'));
            $this->line('  AI Confidence: ' . ($ai['confidence'] ?? 'N/A'));
        }
    }

    private function testOnBooking($bookingId)
    {
        $booking = Booking::find($bookingId);
        if (!$booking) {
            $this->error("Booking #{$bookingId} not found");
            return;
        }

        $this->line("Guest: {$booking->guest_first_name} {$booking->guest_last_name}");
        $this->line("Previous Score: {$booking->risk_score}/100");

        ProcessBookingRisk::run($booking);

        $booking->refresh();
        $this->info("New Score: {$booking->risk_score}/100");

        $metadata = $booking->risk_metadata;
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $aiUsed = $decoded['llmUsed'] ?? false;
            $this->line('AI Used: ' . ($aiUsed ? 'YES' : 'NO'));

            if ($aiUsed && isset($decoded['llmResponse'])) {
                $ai = json_decode($decoded['llmResponse'], true);
                if ($ai) {
                    $this->info('AI Analysis:');
                    $this->line('  Score: ' . ($ai['risk_score'] ?? 'N/A'));
                    $this->line('  Confidence: ' . ($ai['confidence'] ?? 'N/A'));
                    $this->line('  Analysis: ' . ($ai['analysis'] ?? 'N/A'));
                }
            }
        }
    }
}