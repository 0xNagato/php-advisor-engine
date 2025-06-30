<?php

namespace App\Console\Commands;

use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Venue;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateVenuesFromCsv extends Command
{
    protected $signature = 'venues:update-from-csv 
                            {csv_path? : Path to the CSV file}
                            {--dry-run : Run without making actual changes}';

    protected $description = 'Update existing venues with data from the CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultFile = 'Venue Onboarding (May 2025) - Venue Onboarding (1).csv';

        // Try multiple possible locations for the CSV file
        $csvPath = $this->argument('csv_path');
        if (! $csvPath) {
            $possiblePaths = [
                base_path($defaultFile),                // Project root
                storage_path('app/'.$defaultFile),    // Storage directory
                public_path($defaultFile),              // Public directory
                $defaultFile,                            // Current directory
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $csvPath = $path;
                    break;
                }
            }

            if (! $csvPath) {
                $csvPath = base_path($defaultFile); // Default to project root if not found
            }
        }

        $isDryRun = $this->option('dry-run');

        if (! file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            $this->info('Please ensure the CSV file is in one of these locations:');
            $this->info('- Project root: '.base_path());
            $this->info('- Storage app directory: '.storage_path('app'));
            $this->info('- Public directory: '.public_path());
            $this->info('Or specify the full path: php artisan venues:update-from-csv /path/to/file.csv');

            return 1;
        }

        $this->info("Using CSV file: {$csvPath}");

        $this->info("Reading CSV file: {$csvPath}");
        $csvRows = array_map('str_getcsv', file($csvPath));
        $headers = array_shift($csvRows); // Remove header row

        // Map header indexes
        $nameIndex = array_search('Venue Name *', $headers);
        $regionIndex = array_search('Region *', $headers);
        $neighborhoodIndex = array_search('Neighborhood *', $headers);
        $specialtiesIndex = array_search('Specialties (comma-separated)', $headers);
        $cuisinesIndex = array_search('Cuisines (comma-separated)', $headers);

        if ($isDryRun) {
            $this->info('Running in dry-run mode - no changes will be made to the database');
        } else {
            $this->info('Running in LIVE mode - changes WILL be made to the database');
            if (! $this->confirm('Do you want to continue with database updates?', true)) {
                $this->info('Operation cancelled by user');

                return 0;
            }
        }

        $partnerUser = $this->findOrCreatePartnerUser();

        $venuesUpdated = 0;
        $venuesSkipped = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar(count($csvRows));
        $progressBar->start();

        foreach ($csvRows as $index => $row) {
            $rowNumber = $index + 2; // Account for 1-based indexing and header row

            $venueName = trim($row[$nameIndex] ?? '');
            $regionName = strtolower(trim($row[$regionIndex] ?? ''));
            $neighborhood = trim($row[$neighborhoodIndex] ?? '');
            $specialtiesStr = trim($row[$specialtiesIndex] ?? '');
            $cuisinesStr = trim($row[$cuisinesIndex] ?? '');

            if (blank($venueName) || blank($regionName)) {
                $errors[] = "Row {$rowNumber}: Empty venue name or region";
                $progressBar->advance();

                continue;
            }

            // Generate slug from region and venue name (same as in Venue model)
            $slug = Str::slug("{$regionName}-{$venueName}");

            // Find venue by slug - use both exact match and LIKE patterns
            $venue = Venue::query()->where('slug', $slug)->first();

            // If not found by exact match, try with LIKE
            if (! $venue) {
                $venue = Venue::query()->where('slug', 'like', $slug.'%')->first();
            }

            // One more attempt with partial match
            if (! $venue) {
                $nameParts = explode(' ', $venueName);
                if (count($nameParts) > 1) {
                    $partialSlug = Str::slug("{$regionName}-".$nameParts[0]);
                    $venue = Venue::query()->where('slug', 'like', $partialSlug.'%')->first();
                }
            }

            if (! $venue) {
                $errors[] = "Row {$rowNumber}: No venue found with slug similar to: {$slug}";
                $venuesSkipped++;
                $progressBar->advance();

                continue;
            }

            // Process venue updates
            try {
                // Setup Neighborhood
                $neighborhoodId = $this->findNeighborhoodId($neighborhood, $regionName);

                // Setup Cuisines
                $cuisineIds = $this->processCuisines($cuisinesStr);

                // Setup Specialties
                $specialtyIds = $this->processSpecialties($specialtiesStr);

                $updateData = [];

                if ($neighborhoodId !== null) {
                    $updateData['neighborhood'] = $neighborhoodId;
                    $this->writeVerbose("Setting neighborhood for {$venue->name} to: {$neighborhoodId} (from {$neighborhood})");
                }

                if (filled($cuisineIds)) {
                    $updateData['cuisines'] = $cuisineIds;
                    $this->writeVerbose("Setting cuisines for {$venue->name} to: ".implode(', ', $cuisineIds)." (from {$cuisinesStr})");
                }

                if (filled($specialtyIds)) {
                    $updateData['specialty'] = $specialtyIds;
                    $this->writeVerbose("Setting specialties for {$venue->name} to: ".implode(', ', $specialtyIds)." (from {$specialtiesStr})");
                }

                if (filled($updateData)) {
                    if (! $isDryRun) {
                        $venue->update($updateData);
                        $this->line("<fg=green>✓</> Updated venue: {$venue->name} (ID: {$venue->id})");
                    } else {
                        $this->line("<fg=yellow>DRY RUN:</> Would update venue: {$venue->name} (ID: {$venue->id})");
                    }
                } else {
                    $this->writeVerbose("No updates needed for venue: {$venue->name}");
                }

                // Setup partner referral
                $shouldUpdatePartner = false;
                $venueUser = $venue->user;

                if ($venueUser && $venueUser->partner_referral_id === null) {
                    $shouldUpdatePartner = true;
                    $this->writeVerbose("Venue user {$venueUser->email} needs partner referral");
                }

                if ($shouldUpdatePartner && ! $isDryRun) {
                    $this->setupPartnerReferral($venue, $partnerUser);
                } elseif ($shouldUpdatePartner) {
                    $this->line("<fg=yellow>DRY RUN:</> Would set partner referral for: {$venueUser->email}");
                }

                $venuesUpdated++;

                $this->writeVerbose("Updated venue: {$venueName} ({$venue->slug})");

            } catch (Exception $e) {
                $errors[] = "Row {$rowNumber}: Error updating venue {$venueName}: {$e->getMessage()}";
                Log::error("Error updating venue {$venueName} from CSV", [
                    'error' => $e->getMessage(),
                    'venue' => $venueName,
                    'slug' => $slug,
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Get counts from database to verify changes
        $venuesWithNeighborhood = Venue::query()->whereNotNull('neighborhood')->where('region', 'ibiza')->count();
        $venuesWithCuisines = Venue::query()->whereNotNull('cuisines')->where('region', 'ibiza')->count();
        $venuesWithSpecialties = Venue::query()->whereNotNull('specialty')->where('region', 'ibiza')->count();

        $totalVenues = Venue::query()->where('region', 'ibiza')->count();

        if ($isDryRun) {
            $this->info('<fg=yellow>DRY RUN COMPLETED</> - No changes were made to the database');
        } else {
            $this->info('<fg=green>PROCESS COMPLETED</> - Changes have been saved to the database');
        }

        $this->info('Summary:');
        $this->info("- Venues processed: {$venuesUpdated}");
        $this->info("- Venues skipped: {$venuesSkipped}");
        $this->newLine();

        $this->info('Current database stats (Ibiza region):');
        $this->info("- Total venues: {$totalVenues}");
        $this->info("- Venues with neighborhood: {$venuesWithNeighborhood}");
        $this->info("- Venues with cuisines: {$venuesWithCuisines}");
        $this->info("- Venues with specialties: {$venuesWithSpecialties}");

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach ($errors as $error) {
                $this->warn("  - {$error}");
            }
        }

        $this->newLine();
        $this->info('Use the following flags for more detailed output:');
        $this->info('  -v    Show verbose output with additional details');
        $this->info('  -vv   Show very verbose output with debug information');
        $this->info('  --no-ansi   Disable colors in output for logging');

        return 0;
    }

    /**
     * Find neighborhood ID from name and region
     */
    private function findNeighborhoodId(string $neighborhoodName, string $regionName): ?string
    {
        // Map some common variations of neighborhood names to their IDs
        $neighborhoodMap = [
            'ibiza' => [
                'San José' => 'san_jose',
                'San Jose' => 'san_jose',
                'Santa Gertrudis' => 'santa_gertrudis',
                'Santa Eulalia' => 'santa_eularia',
                'Santa Eulària' => 'santa_eularia',
                'Ibiza Town' => 'ibiza_town',
                'San Antonio' => 'sant_antoni',
                'San Juan' => 'san_miguel',  // Closest match available
                'Formentera' => 'formentera',
            ],
        ];

        // Direct mapping if available
        if (isset($neighborhoodMap[$regionName][$neighborhoodName])) {
            return $neighborhoodMap[$regionName][$neighborhoodName];
        }

        // If no direct mapping, try to find by name
        $neighborhoods = Neighborhood::query()->where('region', $regionName)->get();

        foreach ($neighborhoods as $neighborhood) {
            // Normalize both strings for comparison
            $normalizedNeighborhood = Str::of($neighborhood->name)->lower()->replace([' ', '-', "'"], '');
            $normalizedInput = Str::of($neighborhoodName)->lower()->replace([' ', '-', "'"], '');

            if ($normalizedNeighborhood == $normalizedInput ||
                Str::contains($normalizedNeighborhood, $normalizedInput) ||
                Str::contains($normalizedInput, $normalizedNeighborhood)) {
                return $neighborhood->id;
            }
        }

        // Fall back to a more loose match
        foreach ($neighborhoods as $neighborhood) {
            $similarityScore = similar_text(Str::lower($neighborhood->name), Str::lower($neighborhoodName), $percent);
            if ($percent > 70) {
                return $neighborhood->id;
            }
        }

        // If no match found, log and return null
        $this->writeVerbose("No neighborhood match found for '{$neighborhoodName}' in region '{$regionName}'");

        return null;
    }

    /**
     * Process cuisines string into an array of cuisine IDs
     */
    private function processCuisines(string $cuisinesStr): array
    {
        if (blank($cuisinesStr)) {
            return [];
        }

        $cuisineNames = array_map('trim', explode(',', $cuisinesStr));
        $cuisineIds = [];

        // Map cuisine names to their IDs
        $cuisineMap = [
            'Mediterranean' => 'mediterranean',
            'mediterranean' => 'mediterranean',
            'Italian' => 'italian',
            'italian' => 'italian',
            'Japanese' => 'japanese',
            'japanese' => 'japanese',
            'Spanish' => 'spanish',
            'spanish' => 'spanish',
            'Seafood' => 'seafood',
            'seafood' => 'seafood',
            'International' => 'international',
            'international' => 'international',
            'Fusion' => 'fusion',
            'fusion' => 'fusion',
            'Peruvian' => 'peruvian',
            'peruvian' => 'peruvian',
            'Greek' => 'greek',
            'greek' => 'greek',
            'Middle Eastern' => 'middle_eastern',
            'middle eastern' => 'middle_eastern',
            'Asian' => 'asian',
            'asian' => 'asian',
            'Steakhouse' => 'steakhouse',
            'steakhouse' => 'steakhouse',
            'Grill' => 'grill',
            'grill' => 'grill',
            'French' => 'french',
            'french' => 'french',
        ];

        foreach ($cuisineNames as $name) {
            // Try exact match
            if (isset($cuisineMap[$name])) {
                $cuisineIds[] = $cuisineMap[$name];

                continue;
            }

            // Try case-insensitive match
            $found = false;
            foreach ($cuisineMap as $mapName => $mapId) {
                if (strcasecmp($mapName, $name) === 0) {
                    $cuisineIds[] = $mapId;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                continue;
            }

            // Try to find by name
            $cuisine = Cuisine::all()->first(fn ($cuisine) => strcasecmp((string) $cuisine->name, $name) === 0 ||
                   Str::contains(Str::lower($cuisine->name), Str::lower($name)) ||
                   Str::contains(Str::lower($name), Str::lower($cuisine->name)));

            if ($cuisine) {
                $cuisineIds[] = $cuisine->id;

                continue;
            }

            // Log if we couldn't find a match
            $this->writeVerbose("Could not find cuisine match for: {$name}");
        }

        return array_unique($cuisineIds);
    }

    /**
     * Process specialties string into an array of specialty IDs
     */
    private function processSpecialties(string $specialtiesStr): array
    {
        if (blank($specialtiesStr)) {
            return [];
        }

        $specialtyNames = array_map('trim', explode(',', $specialtiesStr));
        $specialtyIds = [];

        // Map specialty names to their IDs
        $specialtyMap = [
            'Sunset view' => 'sunset_view',
            'sunset view' => 'sunset_view',
            'On the Beach' => 'on_the_beach',
            'on the beach' => 'on_the_beach',
            'Farm-to-Table' => 'farm_to_table',
            'farm-to-table' => 'farm_to_table',
            'Farm-to-table' => 'farm_to_table',
            'Live Music/DJ' => 'live_music_dj',
            'live music/dj' => 'live_music_dj',
            'Michelin/Repsol Recognition' => 'michelin_repsol_recognition',
            'michelin/repsol recognition' => 'michelin_repsol_recognition',
            'Rooftop' => 'rooftop',
            'rooftop' => 'rooftop',
            'Fine Dining' => 'fine_dining',
            'fine dining' => 'fine_dining',
            'Scenic view' => 'scenic_view',
            'scenic view' => 'scenic_view',
            'Waterfront' => 'waterfront',
            'waterfront' => 'waterfront',
        ];

        foreach ($specialtyNames as $name) {
            // Try exact match
            if (isset($specialtyMap[$name])) {
                $specialtyIds[] = $specialtyMap[$name];

                continue;
            }

            // Try case-insensitive match
            $found = false;
            foreach ($specialtyMap as $mapName => $mapId) {
                if (strcasecmp($mapName, $name) === 0) {
                    $specialtyIds[] = $mapId;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                continue;
            }

            // Try to find by name
            $specialty = Specialty::all()->first(fn ($specialty) => strcasecmp((string) $specialty->name, $name) === 0 ||
                   Str::contains(Str::lower($specialty->name), Str::lower($name)) ||
                   Str::contains(Str::lower($name), Str::lower($specialty->name)));

            if ($specialty) {
                $specialtyIds[] = $specialty->id;

                continue;
            }

            // Log if we couldn't find a match
            $this->writeVerbose("Could not find specialty match for: {$name}");
        }

        return array_unique($specialtyIds);
    }

    /**
     * Find or create Kevin Dash as the partner user
     */
    private function findOrCreatePartnerUser(): User
    {
        $partnerEmail = 'Kevin+IbizaPartner@primavip.co';
        $partnerUser = User::query()->where('email', $partnerEmail)->first();

        if (! $partnerUser) {
            // Create the partner user
            $partnerUser = User::query()->create([
                'first_name' => 'Kevin',
                'last_name' => 'Dash',
                'email' => $partnerEmail,
                'phone' => '17865147601',
                'password' => bcrypt(Str::random(16)),
                'region' => 'ibiza',
            ]);

            // Assign partner role
            $partnerUser->assignRole('partner');

            // Create partner record
            Partner::query()->create([
                'user_id' => $partnerUser->id,
                'percentage' => 15, // Default percentage
                'company_name' => 'Prima Ibiza Partner',
            ]);

            $this->info("Created new partner user: {$partnerEmail}");
        }

        return $partnerUser;
    }

    /**
     * Setup partner referral for a venue
     */
    private function setupPartnerReferral(Venue $venue, User $partnerUser): void
    {
        $venueUser = $venue->user;

        if (! $venueUser) {
            $this->writeVerbose("No user found for venue: {$venue->name}");

            return;
        }

        // Check if partner referral already exists
        if ($venueUser->partner_referral_id) {
            $this->writeVerbose("Partner referral already exists for venue: {$venue->name}");

            return;
        }

        // Update the venue user with partner referral
        $venueUser->update([
            'partner_referral_id' => $partnerUser->partner->id,
        ]);

        // Create a referral record
        Referral::query()->create([
            'referrer_id' => $partnerUser->id,
            'user_id' => $venueUser->id,
            'email' => $venueUser->email,
            'phone' => $venueUser->phone,
            'secured_at' => now(),
            'type' => 'venue',
            'referrer_type' => 'partner',
            'first_name' => $venueUser->first_name,
            'last_name' => $venueUser->last_name,
            'region_id' => 'ibiza',
            'company_name' => $venue->name,
        ]);

        $this->writeVerbose("Setup partner referral for venue: {$venue->name}");
    }

    /**
     * Write verbose output if verbosity is set
     */
    private function writeVerbose(string $message): void
    {
        if ($this->getOutput()->isVerbose()) {
            $this->line("<fg=gray>{$message}</>");
        }
    }

    /**
     * Log detailed object information when in very verbose mode
     */
    private function logVerboseDetails(string $label, $data): void
    {
        if ($this->getOutput()->isVeryVerbose()) {
            $this->line("<fg=blue>{$label}:</>");
            $this->line('<fg=blue>'.json_encode($data, JSON_PRETTY_PRINT).'</>');
        }
    }
}
