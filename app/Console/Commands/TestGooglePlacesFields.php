<?php

namespace App\Console\Commands;

use App\Services\GooglePlacesService;
use Illuminate\Console\Command;

class TestGooglePlacesFields extends Command
{
    protected $signature = 'test:google-fields {query : The search query}';
    protected $description = 'Test what fields Google Places API returns for a query';

    public function handle(GooglePlacesService $googlePlaces): int
    {
        $query = $this->argument('query');
        $this->info("Testing Google Places API with query: {$query}");
        
        $result = $googlePlaces->searchPlace($query);
        
        if (!$result) {
            $this->error('No results found');
            return Command::FAILURE;
        }
        
        $this->info("\nRaw API Response Fields:");
        $this->line("Place ID: " . ($result->placeId ?? 'null'));
        $this->line("Name: " . ($result->name ?? 'null'));
        $this->line("Address: " . ($result->formattedAddress ?? 'null'));
        $this->line("Rating: " . ($result->rating ?? 'null'));
        $this->line("Price Level: " . ($result->priceLevel ?? 'null'));
        $this->line("User Ratings Total: " . ($result->userRatingsTotal ?? 'null'));
        $this->line("Editorial Summary: " . ($result->editorialSummary ?? 'null'));
        $this->line("Generative Summary: " . ($result->generativeSummary ?? 'null'));
        $this->line("Primary Type: " . ($result->primaryType ?? 'null'));
        $this->line("Types: " . json_encode($result->types ?? []));
        
        return Command::SUCCESS;
    }
}