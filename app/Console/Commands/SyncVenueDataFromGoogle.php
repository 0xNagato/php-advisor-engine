<?php

namespace App\Console\Commands;

use App\Data\VenueMetadata;
use App\Models\Venue;
use App\Services\GooglePlacesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVenueDataFromGoogle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:sync-google-data 
                            {--venue= : Sync a specific venue by ID}
                            {--limit= : Number of venues to sync per run (default: all)}
                            {--force : Force sync even if recently synced}
                            {--skip-photos : Skip downloading and uploading photos}
                            {--overwrite-address : Overwrite existing addresses with Google data}
                            {--overwrite-description : Overwrite existing descriptions with Google data}
                            {--ratings-only : Only update ratings}
                            {--descriptions-only : Only update descriptions}
                            {--location-only : Only update latitude and longitude coordinates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync venue data from Google Places API (ratings, descriptions, cuisines, specialties, photos, addresses, coordinates)';

    /**
     * Execute the console command.
     */
    public function handle(GooglePlacesService $googlePlaces): int
    {
        if (empty(config('services.google.places_api_key'))) {
            $this->error('Google Places API key is not configured. Please set GOOGLE_PLACES_API_KEY in your .env file.');

            return Command::FAILURE;
        }

        $this->info('Starting Google Places sync...');

        $query = Venue::query();

        // If a specific venue is requested
        if ($venueId = $this->option('venue')) {
            $query->where('id', $venueId);
        } elseif (! $this->option('force')) {
            // Only sync venues that haven't been synced in the last 24 hours
            $query->where(function ($q) {
                $q->whereNull('metadata->lastSyncedAt')
                    ->orWhereRaw("(metadata->>'lastSyncedAt')::timestamp < ?", [
                        now()->subDay()->toISOString(),
                    ]);
            });
        }

        $limit = $this->option('limit');
        $venues = $limit ? $query->limit($limit)->get() : $query->get();

        if ($venues->isEmpty()) {
            $this->info('No venues to sync.');

            return Command::SUCCESS;
        }

        $this->info("Syncing {$venues->count()} venues...");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        // Create progress bar
        $progressBar = $this->output->createProgressBar($venues->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        foreach ($venues as $venue) {
            $progressBar->setMessage("Syncing: {$venue->name}");
            $progressBar->advance();

            try {
                // Build search query
                $searchQuery = $venue->name;
                if ($venue->address) {
                    $searchQuery .= ' '.$venue->address;
                }

                // Search for the venue
                $placeData = $googlePlaces->searchPlace($searchQuery, $venue->region);

                if ($placeData) {
                    // If we have a place_id but need more details, fetch them
                    if ($placeData->placeId && ! $placeData->rating) {
                        $detailedData = $googlePlaces->getPlaceDetails($placeData->placeId);
                        if ($detailedData) {
                            $placeData = $detailedData;
                        }
                    }

                    // Check if we're only updating specific fields
                    $ratingsOnly = $this->option('ratings-only');
                    $descriptionsOnly = $this->option('descriptions-only');
                    $locationOnly = $this->option('location-only');

                    if (! $ratingsOnly && ! $descriptionsOnly && ! $locationOnly) {
                        // Normal flow - update everything as appropriate
                        // Handle address updates based on flags
                        if ($this->shouldUpdateAddress($venue, $placeData)) {
                            $venue->address = $placeData->formattedAddress;
                        }

                        // Handle description updates based on flags
                        if ($this->shouldUpdateDescription($venue, $placeData)) {
                            // Try editorialSummary first, fall back to generativeSummary
                            $venue->description = $placeData->editorialSummary ?? $placeData->generativeSummary;
                        }

                        // Update latitude and longitude if available
                        if ($placeData->latitude !== null) {
                            $venue->latitude = $placeData->latitude;
                        }
                        if ($placeData->longitude !== null) {
                            $venue->longitude = $placeData->longitude;
                        }

                        // Update metadata (always update metadata fields)
                        $venue->updateMetadataFromGoogle($placeData, $this->option('skip-photos'));
                    } elseif ($ratingsOnly) {
                        // Only update rating in metadata
                        $metadata = $venue->metadata ?? new VenueMetadata;
                        if ($placeData->rating !== null) {
                            $metadata->googleRating = $placeData->rating;
                            $metadata->googleRatingCount = $placeData->userRatingsTotal;
                            $metadata->lastSyncedAt = now();
                            $venue->metadata = $metadata;
                        }
                    } elseif ($descriptionsOnly) {
                        // Only update description
                        if ($this->shouldUpdateDescription($venue, $placeData)) {
                            // Try editorialSummary first, fall back to generativeSummary
                            $venue->description = $placeData->editorialSummary ?? $placeData->generativeSummary;
                        }
                        // Update sync timestamp
                        $metadata = $venue->metadata ?? new VenueMetadata;
                        $metadata->lastSyncedAt = now();
                        $venue->metadata = $metadata;
                    } elseif ($locationOnly) {
                        // Only update coordinates
                        if ($placeData->latitude !== null) {
                            $venue->latitude = $placeData->latitude;
                        }
                        if ($placeData->longitude !== null) {
                            $venue->longitude = $placeData->longitude;
                        }
                        // Update sync timestamp
                        $metadata = $venue->metadata ?? new VenueMetadata;
                        $metadata->lastSyncedAt = now();
                        $venue->metadata = $metadata;
                    }

                    $venue->save();

                    $successCount++;
                } else {
                    $failCount++;
                    Log::warning('No Google Places results found', [
                        'venue_id' => $venue->id,
                        'venue_name' => $venue->name,
                    ]);
                }

                // Rate limiting - Google Places API has quotas
                sleep(1);
            } catch (\Exception $e) {
                $failCount++;
                Log::error('Google Places sync error', [
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Sync complete!');
        $this->info("Success: {$successCount}, Failed: {$failCount}");

        // Show summary table
        if ($successCount > 0) {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Venues Synced', $successCount],
                    ['Failed Syncs', $failCount],
                    ['Photos Skipped', $this->arePhotosSkipped() ? 'Yes' : 'No'],
                    ['Address Updates', $this->getAddressUpdateMode()],
                    ['Description Updates', $this->getDescriptionUpdateMode()],
                    ['Update Mode', $this->getUpdateMode()],
                ]
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Determine if address should be updated
     */
    private function shouldUpdateAddress(Venue $venue, $placeData): bool
    {
        if (! $placeData->formattedAddress) {
            return false;
        }

        if ($this->option('overwrite-address')) {
            return true;
        }

        // Always fill missing addresses
        if (blank($venue->address)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if description should be updated
     */
    private function shouldUpdateDescription(Venue $venue, $placeData): bool
    {
        // Check if we have any description available
        $hasDescription = $placeData->editorialSummary || $placeData->generativeSummary;
        if (! $hasDescription) {
            return false;
        }

        if ($this->option('overwrite-description')) {
            return true;
        }

        // Only update if venue has no description
        return blank($venue->description);
    }

    /**
     * Get address update mode for summary
     */
    private function getAddressUpdateMode(): string
    {
        if ($this->option('overwrite-address')) {
            return 'Overwrite All';
        }

        return 'Fill Missing Only';
    }

    /**
     * Get description update mode for summary
     */
    private function getDescriptionUpdateMode(): string
    {
        if ($this->option('overwrite-description')) {
            return 'Overwrite All';
        }

        return 'Fill Missing Only';
    }

    /**
     * Check if photos are being skipped
     */
    private function arePhotosSkipped(): bool
    {
        // Photos are skipped if any of these conditions are true:
        // 1. --skip-photos flag is used
        // 2. --descriptions-only flag is used (only updating descriptions)
        // 3. --ratings-only flag is used (only updating ratings)
        // 4. --location-only flag is used (only updating coordinates)
        return $this->option('skip-photos') ||
               $this->option('descriptions-only') ||
               $this->option('ratings-only') ||
               $this->option('location-only');
    }

    /**
     * Get the update mode for summary
     */
    private function getUpdateMode(): string
    {
        if ($this->option('ratings-only')) {
            return 'Ratings Only';
        }

        if ($this->option('descriptions-only')) {
            return 'Descriptions Only';
        }

        if ($this->option('location-only')) {
            return 'Coordinates Only';
        }

        return 'Full Sync';
    }
}
