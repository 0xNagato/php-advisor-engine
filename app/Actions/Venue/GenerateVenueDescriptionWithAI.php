<?php

namespace App\Actions\Venue;

use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateVenueDescriptionWithAI
{
    use AsAction;

    private const ANTHROPIC_MODEL = 'claude-opus-4-1-20250805';
    private const OPENAI_MODEL = 'gpt-4-turbo-preview';

    public function handle(Venue $venue, string $provider = 'anthropic'): ?string
    {
        $apiKey = $provider === 'anthropic' 
            ? config('services.anthropic.api_key')
            : config('services.openai.api_key');

        if (empty($apiKey)) {
            throw new \Exception("API key for {$provider} is not configured.");
        }

        $prompt = $this->buildDetailedPrompt($venue);

        return $provider === 'anthropic' 
            ? $this->generateWithAnthropic($prompt, $apiKey)
            : $this->generateWithOpenAI($prompt, $apiKey);
    }

    private function buildDetailedPrompt(Venue $venue): string
    {
        $metadata = $venue->metadata;
        $context = [];

        // Basic info
        $context[] = "Restaurant: {$venue->name}";
        if ($venue->address) {
            $context[] = "Address: {$venue->address}";
        }
        $context[] = "Location: {$venue->region}";

        // Google data
        if ($metadata?->rating) {
            $ratingText = number_format($metadata->rating, 1);
            $context[] = "Rated {$ratingText}/5.0 stars on Google ({$metadata->reviewCount} reviews)";
        }

        // Cuisine and specialties
        if ($venue->cuisines) {
            $context[] = "Cuisine types: " . implode(', ', array_map(fn($c) => ucwords(str_replace('_', ' ', $c)), $venue->cuisines));
        }
        if ($venue->specialty) {
            $context[] = "Known for: " . implode(', ', $venue->specialty);
        }

        // Price level
        if ($metadata?->priceLevel) {
            $priceDescriptions = [
                1 => 'budget-friendly',
                2 => 'moderately priced',
                3 => 'upscale',
                4 => 'fine dining'
            ];
            $context[] = "Price range: " . ($priceDescriptions[$metadata->priceLevel] ?? 'varies');
        }

        // Features and amenities
        $features = [];
        $attrs = $metadata?->googleAttributes ?? [];
        if ($attrs['serves_wine'] ?? false) $features[] = 'extensive wine selection';
        if ($attrs['serves_beer'] ?? false) $features[] = 'craft beer menu';
        if ($attrs['serves_cocktails'] ?? false) $features[] = 'creative cocktails';
        if ($attrs['outdoor_seating'] ?? false) $features[] = 'outdoor dining';
        if ($attrs['live_music'] ?? false) $features[] = 'live music entertainment';

        if (!empty($features)) {
            $context[] = "Offers: " . implode(', ', $features);
        }

        // Existing descriptions to reference
        $existingDescriptions = [];
        if ($metadata?->googleEditorialSummary) {
            $existingDescriptions[] = "Previous summary: " . $metadata->googleEditorialSummary;
        }
        if ($metadata?->googleGenerativeSummary) {
            $existingDescriptions[] = "AI summary: " . $metadata->googleGenerativeSummary;
        }

        $contextText = implode("\n", $context);
        $existingText = !empty($existingDescriptions) ? "\n\n" . implode("\n", $existingDescriptions) : '';

        return <<<EOT
You are a professional restaurant copywriter creating descriptions for a restaurant booking platform.

Write a compelling, detailed description for this restaurant that will help diners understand what makes it special.
The description should be 3-4 sentences (60-100 words) and include:
- The dining experience and atmosphere
- Signature dishes or cuisine highlights
- What sets this restaurant apart
- Any notable features or amenities

Restaurant Information:
{$contextText}{$existingText}

Write a professional, engaging description that would appeal to diners looking for a memorable dining experience.
Focus on what makes this venue unique and worth visiting.
Use descriptive language but keep it factual and based on the provided information.
Do not use marketing clichés or overly promotional language.

Write only the description text, nothing else:
EOT;
    }

    private function generateWithAnthropic(string $prompt, string $apiKey): ?string
    {
        $response = Http::timeout(30)->withHeaders([
            'anthropic-version' => '2023-06-01',
            'x-api-key' => $apiKey,
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => self::ANTHROPIC_MODEL,
            'max_tokens' => 400,
            'temperature' => 0.7,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();
        return trim($data['content'][0]['text'] ?? '');
    }

    private function generateWithOpenAI(string $prompt, string $apiKey): ?string
    {
        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => self::OPENAI_MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional restaurant copywriter. Write engaging, factual descriptions.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 400,
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        return trim($data['choices'][0]['message']['content'] ?? '');
    }
}