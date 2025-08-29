<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateVenueDescriptions extends Command
{
    protected $signature = 'venues:generate-descriptions 
                            {--venue= : Generate description for a specific venue by ID}
                            {--limit= : Number of venues to process per run (default: all)}
                            {--provider=anthropic : AI provider to use (anthropic or openai)}
                            {--force : Force regenerate even if venue has a description}
                            {--dry-run : Show what would be generated without saving}';

    protected $description = 'Generate AI-powered descriptions for venues based on their data';

    private const ANTHROPIC_MODEL = 'claude-opus-4-1-20250805';
    private const OPENAI_MODEL = 'gpt-4-turbo-preview';

    public function handle(): int
    {
        $provider = $this->option('provider');
        
        if (!in_array($provider, ['anthropic', 'openai'])) {
            $this->error('Invalid provider. Use "anthropic" or "openai".');
            return Command::FAILURE;
        }

        $apiKey = $provider === 'anthropic' 
            ? config('services.anthropic.api_key')
            : config('services.openai.api_key');

        if (empty($apiKey)) {
            $this->error("API key for {$provider} is not configured.");
            return Command::FAILURE;
        }

        $query = Venue::query()
            ->whereIn('status', ['approved', 'active']);

        // If a specific venue is requested
        if ($venueId = $this->option('venue')) {
            $query->where('id', $venueId);
        } elseif (!$this->option('force')) {
            // Only process venues without descriptions
            $query->where(function ($q) {
                $q->whereNull('description')
                    ->orWhere('description', '');
            });
        }

        $limit = $this->option('limit');
        $venues = $limit ? $query->limit($limit)->get() : $query->get();

        if ($venues->isEmpty()) {
            $this->info('No venues to process.');
            return Command::SUCCESS;
        }

        $this->info("Generating descriptions for {$venues->count()} venues using {$provider}...");
        $progressBar = $this->output->createProgressBar($venues->count());
        $progressBar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($venues as $venue) {
            $progressBar->advance();

            try {
                $description = $this->generateDescription($venue, $provider, $apiKey);
                
                if ($description) {
                    if ($this->option('dry-run')) {
                        $this->newLine();
                        $this->info("Would generate for {$venue->name}:");
                        $this->line($description);
                    } else {
                        $venue->description = $description;
                        $venue->save();
                    }
                    $successCount++;
                } else {
                    $failCount++;
                }

                // Rate limiting
                sleep(1);
            } catch (\Exception $e) {
                $failCount++;
                Log::error('AI description generation error', [
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Generation complete! Success: {$successCount}, Failed: {$failCount}");

        return Command::SUCCESS;
    }

    private function generateDescription(Venue $venue, string $provider, string $apiKey): ?string
    {
        $prompt = $this->buildPrompt($venue);

        if ($provider === 'anthropic') {
            return $this->generateWithAnthropic($prompt, $apiKey);
        } else {
            return $this->generateWithOpenAI($prompt, $apiKey);
        }
    }

    private function buildPrompt(Venue $venue): string
    {
        $metadata = $venue->metadata;
        $features = [];

        // Build feature list from metadata
        if ($metadata?->rating) {
            $features[] = "Google rating: {$metadata->rating}/5 ({$metadata->reviewCount} reviews)";
        }
        if ($venue->cuisines) {
            $features[] = "Cuisines: " . implode(', ', $venue->cuisines);
        }
        if ($venue->specialty) {
            $features[] = "Specialties: " . implode(', ', $venue->specialty);
        }
        if ($metadata?->priceLevel) {
            $priceMap = [1 => '$', 2 => '$$', 3 => '$$$', 4 => '$$$$'];
            $features[] = "Price level: " . ($priceMap[$metadata->priceLevel] ?? 'Unknown');
        }
        if ($metadata?->googlePrimaryType) {
            $features[] = "Type: " . str_replace('_', ' ', $metadata->googlePrimaryType);
        }
        
        // Add boolean features
        $booleanFeatures = [];
        $attrs = $metadata?->googleAttributes ?? [];
        if ($attrs['serves_wine'] ?? false) $booleanFeatures[] = 'wine';
        if ($attrs['serves_beer'] ?? false) $booleanFeatures[] = 'beer';
        if ($attrs['serves_cocktails'] ?? false) $booleanFeatures[] = 'cocktails';
        if ($attrs['outdoor_seating'] ?? false) $booleanFeatures[] = 'outdoor seating';
        if ($attrs['live_music'] ?? false) $booleanFeatures[] = 'live music';
        
        if (!empty($booleanFeatures)) {
            $features[] = "Features: " . implode(', ', $booleanFeatures);
        }

        $featureText = !empty($features) ? "\n" . implode("\n", $features) : '';

        return <<<EOT
Write a compelling, SEO-friendly restaurant description for {$venue->name} located in {$venue->region}. 
The description should be 2-3 sentences long and highlight what makes this venue special.
Make it engaging and informative for potential diners.
{$featureText}

Write only the description text, nothing else.
EOT;
    }

    private function generateWithAnthropic(string $prompt, string $apiKey): ?string
    {
        $response = Http::withHeaders([
            'anthropic-version' => '2023-06-01',
            'x-api-key' => $apiKey,
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => self::ANTHROPIC_MODEL,
            'max_tokens' => 300,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ])->json();

        return $response['content'][0]['text'] ?? null;
    }

    private function generateWithOpenAI(string $prompt, string $apiKey): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => self::OPENAI_MODEL,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 300,
            'temperature' => 0.7,
        ])->json();

        return $response['choices'][0]['message']['content'] ?? null;
    }
}