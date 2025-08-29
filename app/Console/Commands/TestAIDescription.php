<?php

namespace App\Console\Commands;

use App\Actions\Venue\GenerateVenueDescriptionWithAI;
use App\Models\Venue;
use Illuminate\Console\Command;

class TestAIDescription extends Command
{
    protected $signature = 'test:ai-description {venue_id} {--provider=anthropic}';
    protected $description = 'Test AI description generation for a specific venue';

    public function handle(GenerateVenueDescriptionWithAI $generator): int
    {
        $venueId = $this->argument('venue_id');
        $provider = $this->option('provider');
        
        $venue = Venue::find($venueId);
        
        if (!$venue) {
            $this->error("Venue with ID {$venueId} not found.");
            return Command::FAILURE;
        }
        
        $this->info("Testing AI description for: {$venue->name}");
        $this->info("Provider: {$provider}");
        $this->newLine();
        
        try {
            $description = $generator->handle($venue, $provider);
            
            $this->info("Generated Description:");
            $this->line($description);
            
            $this->newLine();
            $this->info("Current Description:");
            $this->line($venue->description ?: 'None');
            
            $this->newLine();
            $this->info("Metadata:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Rating', $venue->metadata?->rating ?? 'N/A'],
                    ['Price Level', $venue->metadata?->getPriceLevelDisplay() ?? 'N/A'],
                    ['Review Count', $venue->metadata?->reviewCount ?? 'N/A'],
                    ['Primary Type', $venue->metadata?->googlePrimaryType ?? 'N/A'],
                    ['Google Editorial', $venue->metadata?->googleEditorialSummary ?? 'N/A'],
                    ['Google Generative', $venue->metadata?->googleGenerativeSummary ?? 'N/A'],
                ]
            );
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}