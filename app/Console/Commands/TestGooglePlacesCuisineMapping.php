<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\GooglePlacesToCuisineMapper;
use Illuminate\Console\Command;

class TestGooglePlacesCuisineMapping extends Command
{
    protected $signature = 'venues:test-google-cuisine-mapping 
                            {--venue= : Test mapping for a specific venue ID}
                            {--show-mappings : Show all available mappings}';

    protected $description = 'Test Google Places to Cuisine/Specialty mapping';

    public function handle(GooglePlacesToCuisineMapper $mapper): int
    {
        if ($this->option('show-mappings')) {
            $this->showAvailableMappings($mapper);

            return Command::SUCCESS;
        }

        if ($venueId = $this->option('venue')) {
            $this->testVenueMapping($venueId, $mapper);

            return Command::SUCCESS;
        }

        // Test with venues that have Google metadata
        $venuesWithGoogleData = Venue::query()->whereNotNull('metadata->googleTypes')
            ->limit(3)
            ->get();

        if ($venuesWithGoogleData->isEmpty()) {
            $this->info('No venues found with Google Types data. Run venues:sync-google-data first.');

            return Command::SUCCESS;
        }

        $this->info('Testing Google Places to Cuisine/Specialty mapping...');
        $this->newLine();

        foreach ($venuesWithGoogleData as $venue) {
            $this->testVenueMapping($venue->id, $mapper);
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    private function testVenueMapping(int $venueId, GooglePlacesToCuisineMapper $mapper): void
    {
        $venue = Venue::query()->find($venueId);
        if (! $venue) {
            $this->error("Venue {$venueId} not found");

            return;
        }

        $this->info("Venue: {$venue->name} (ID: {$venue->id})");

        // Show current data
        $this->line('Current cuisines: '.json_encode($venue->cuisines));
        $this->line('Current specialties: '.json_encode($venue->specialty));

        if (! $venue->metadata || ! $venue->metadata->googleTypes) {
            $this->warn('No Google Types data available for this venue');

            return;
        }

        $this->line('Google Types: '.json_encode($venue->metadata->googleTypes));
        $this->line('Google Primary Type: '.($venue->metadata->googlePrimaryType ?? 'None'));
        $this->line('Google Attributes: '.json_encode($venue->metadata->googleAttributes));

        // Test mapping
        $mappedCuisines = $mapper->mapToCuisines($venue->metadata->googleTypes);
        $mappedSpecialties = $mapper->mapToSpecialties(
            $venue->metadata->googleTypes,
            $venue->metadata->googleAttributes
        );

        $this->info('Mapped cuisines: '.json_encode($mappedCuisines));
        $this->info('Mapped specialties: '.json_encode($mappedSpecialties));

        // Show what would change
        if (! empty($mappedCuisines)) {
            $existingCuisines = is_array($venue->cuisines) ? $venue->cuisines : [];
            $mergedCuisines = array_values(array_unique(array_merge($existingCuisines, $mappedCuisines)));
            $this->comment('→ Would merge cuisines to: '.json_encode($mergedCuisines));
        }

        if (! empty($mappedSpecialties)) {
            $existingSpecialties = is_array($venue->specialty) ? $venue->specialty : [];
            $mergedSpecialties = array_values(array_unique(array_merge($existingSpecialties, $mappedSpecialties)));
            $this->comment('→ Would merge specialties to: '.json_encode($mergedSpecialties));
        }
    }

    private function showAvailableMappings(GooglePlacesToCuisineMapper $mapper): void
    {
        $this->info('Available Google Places Type → Cuisine Mappings:');
        $this->newLine();

        foreach ($mapper->getAvailableGoogleCuisineTypes() as $googleType) {
            $mapped = $mapper->mapToCuisines([$googleType]);
            $this->line("  {$googleType} → ".implode(', ', $mapped));
        }

        $this->newLine();
        $this->info('Available Google Places Type/Attribute → Specialty Mappings:');
        $this->newLine();

        foreach ($mapper->getAvailableGoogleSpecialtyMappings() as $googleTypeOrAttribute) {
            if (in_array($googleTypeOrAttribute, ['live_music', 'outdoor_seating'])) {
                // This is an attribute
                $mapped = $mapper->mapToSpecialties(null, [$googleTypeOrAttribute => true]);
            } else {
                // This is a type
                $mapped = $mapper->mapToSpecialties([$googleTypeOrAttribute], null);
            }
            $this->line("  {$googleTypeOrAttribute} → ".implode(', ', $mapped));
        }
    }
}
