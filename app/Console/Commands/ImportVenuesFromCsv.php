<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\Specialty;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportVenuesFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:import-csv {file=storage/venues.csv} {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing venues from CSV file with specific fields only';

    private array $stats = [
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'not_found' => 0,
        'errors' => 0,
    ];

    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Starting venue update from: {$filePath}");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be saved');
        }

        // Read and parse CSV
        $csvData = $this->parseCsv($filePath);

        if (empty($csvData)) {
            $this->error('No data found in CSV file');

            return self::FAILURE;
        }

        $this->info('Found '.count($csvData).' venues to update');

        // Process each venue
        foreach ($csvData as $index => $row) {
            $this->processVenue($row, $index + 2, $isDryRun); // +2 for header row and 1-based indexing
        }

        $this->displayResults();

        return self::SUCCESS;
    }

    private function parseCsv(string $filePath): array
    {
        $csvData = [];
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            return [];
        }

        // Read header row
        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return [];
        }

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $csvData[] = array_combine($headers, $row);
            }
        }

        fclose($handle);

        return $csvData;
    }

    private function processVenue(array $row, int $lineNumber, bool $isDryRun): void
    {
        $this->stats['processed']++;

        try {
            // Validate required fields
            if (empty($row['restaurant_name'])) {
                $this->logError($lineNumber, 'Missing restaurant name');

                return;
            }

            // Prepare venue data
            $venueData = $this->prepareVenueData($row);

            if ($isDryRun) {
                $this->displayVenueData($venueData, $lineNumber);

                return;
            }

            // Only update existing venues
            $existingVenue = Venue::where('name', $venueData['name'])->first();

            if ($existingVenue) {
                $this->updateVenue($existingVenue, $venueData);
                $this->stats['updated']++;
                $this->info("Updated: {$venueData['name']}");
            } else {
                $this->stats['not_found']++;
                $this->warn("Venue not found: {$venueData['name']}");
            }

        } catch (\Exception $e) {
            $this->logError($lineNumber, $e->getMessage());
        }
    }

    private function prepareVenueData(array $row): array
    {
        return [
            'name' => trim($row['restaurant_name']), // Only for venue lookup
            'address' => ! empty($row['google_address']) ? trim($row['google_address']) : null,
            'description' => ! empty($row['short_description']) ? trim($row['short_description']) : null,
            'images' => $this->prepareImages($row['image_url'] ?? ''),
            'cuisines' => $this->parseCuisines($row['cuisine_type'] ?? ''),
            'specialty' => $this->parseSpecialties($row['tags'] ?? ''),
            'neighborhood' => $this->inferNeighborhood($row['city'] ?? ''),
        ];
    }

    private function prepareImages(string $imageUrl): array
    {
        if (empty(trim($imageUrl))) {
            return [];
        }

        try {
            // Download image from URL and upload to DigitalOcean Spaces
            $response = Http::timeout(30)->get(trim($imageUrl));

            if (! $response->successful()) {
                $this->warn("Failed to download image from: {$imageUrl}");

                return [];
            }

            // Get file extension from URL or default to jpg
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $fileName = 'imported-'.time().'-'.uniqid().'.'.$extension;

            // Store to DigitalOcean Spaces
            $path = app()->environment().'/venues/images/'.$fileName;

            Storage::disk('do')->put($path, $response->body());
            Storage::disk('do')->setVisibility($path, 'public');

            $this->info("  ✓ Downloaded and uploaded image: {$fileName}");

            return [$path];

        } catch (\Exception $e) {
            $this->warn("Failed to process image {$imageUrl}: ".$e->getMessage());

            return [];
        }
    }

    private function parseCuisines(string $cuisineTypes): array
    {
        if (empty(trim($cuisineTypes))) {
            return [];
        }

        $cuisines = [];
        $types = array_map('trim', explode(',', $cuisineTypes));

        foreach ($types as $type) {
            if (empty($type)) {
                continue;
            }

            // Try to match with existing cuisines
            $matchedCuisine = $this->findMatchingCuisine($type);
            if ($matchedCuisine) {
                $cuisines[] = $matchedCuisine;
            }
        }

        return array_unique($cuisines);
    }

    private function findMatchingCuisine(string $type): ?string
    {
        $type = strtolower($type);

        // Direct matches
        $directMatches = [
            'american' => 'american',
            'chinese' => 'chinese',
            'french' => 'french',
            'indian' => 'indian',
            'italian' => 'italian',
            'japanese' => 'japanese',
            'korean' => 'korean',
            'mediterranean' => 'mediterranean',
            'mexican' => 'mexican',
            'thai' => 'thai',
            'greek' => 'greek',
            'turkish' => 'turkish',
            'spanish' => 'spanish',
            'seafood' => 'seafood',
            'steakhouse' => 'steakhouse',
            'steakhouses' => 'steakhouse',
            'peruvian' => 'peruvian',
        ];

        if (isset($directMatches[$type])) {
            return $directMatches[$type];
        }

        // Partial matches
        if (str_contains($type, 'asian')) {
            return 'asian';
        }
        if (str_contains($type, 'fusion')) {
            return 'fusion';
        }
        if (str_contains($type, 'middle eastern')) {
            return 'middle_eastern';
        }
        if (str_contains($type, 'international')) {
            return 'international';
        }
        if (str_contains($type, 'grill')) {
            return 'grill';
        }

        return null;
    }

    private function parseSpecialties(string $tags): array
    {
        if (empty(trim($tags))) {
            return [];
        }

        $specialties = [];
        $tagList = array_map('trim', explode(',', $tags));

        foreach ($tagList as $tag) {
            if (empty($tag)) {
                continue;
            }

            $matchedSpecialty = $this->findMatchingSpecialty($tag);
            if ($matchedSpecialty) {
                $specialties[] = $matchedSpecialty;
            }
        }

        return array_unique($specialties);
    }

    private function findMatchingSpecialty(string $tag): ?string
    {
        $tag = strtolower($tag);

        $specialtyMatches = [
            'waterfront' => 'waterfront',
            'sunset' => 'sunset_view',
            'sunset view' => 'sunset_view',
            'scenic' => 'scenic_view',
            'scenic view' => 'scenic_view',
            'beach' => 'on_the_beach',
            'on the beach' => 'on_the_beach',
            'family' => 'family_friendly',
            'family friendly' => 'family_friendly',
            'fine dining' => 'fine_dining',
            'romantic' => 'romantic_atmosphere',
            'live music' => 'live_music_dj',
            'dj' => 'live_music_dj',
            'farm to table' => 'farm_to_table',
            'farm-to-table' => 'farm_to_table',
            'vegan' => 'vegetarian_vegan_options',
            'vegetarian' => 'vegetarian_vegan_options',
            'michelin' => 'michelin_repsol_recognition',
            'repsol' => 'michelin_repsol_recognition',
            'rooftop' => 'rooftop',
        ];

        if (isset($specialtyMatches[$tag])) {
            return $specialtyMatches[$tag];
        }

        // Check if tag contains any of the specialty keywords
        foreach ($specialtyMatches as $keyword => $specialty) {
            if (str_contains($tag, $keyword)) {
                return $specialty;
            }
        }

        return null;
    }

    private function inferRegion(string $city): ?string
    {
        if (empty(trim($city))) {
            return null;
        }

        $city = strtolower(trim($city));

        // Map common cities to region string IDs
        $cityToRegion = [
            'miami' => 'miami',
            'ibiza' => 'ibiza',
            'los angeles' => 'los_angeles',
            'la' => 'los_angeles',
            'mykonos' => 'mykonos',
            'paris' => 'paris',
            'london' => 'london',
            'st tropez' => 'st_tropez',
            'saint tropez' => 'st_tropez',
            'new york' => 'new_york',
            'nyc' => 'new_york',
            'las vegas' => 'las_vegas',
            'vegas' => 'las_vegas',
        ];

        if (isset($cityToRegion[$city])) {
            return $cityToRegion[$city];
        }

        // Try to find region by name using partial matching
        $region = Region::whereRaw('LOWER(name) LIKE ?', ['%'.$city.'%'])->first();

        return $region?->id;
    }

    private function inferNeighborhood(string $city): ?string
    {
        // For now, just return null - neighborhood inference can be added later if needed
        // This would require mapping cities to specific neighborhoods
        return null;
    }

    private function updateVenue(Venue $venue, array $data): void
    {
        DB::transaction(function () use ($venue, $data) {
            $updateData = [];

            // Always update address and description if provided
            if ($data['address'] !== null && $data['address'] !== '') {
                $updateData['address'] = $data['address'];
            }

            if ($data['description'] !== null && $data['description'] !== '') {
                $updateData['description'] = $data['description'];
            }

            // Always update images if provided (replaces existing images)
            if (! empty($data['images'])) {
                $updateData['images'] = $data['images'];
            }

            // Only update neighborhood if venue doesn't have one
            if (isset($data['neighborhood']) && $data['neighborhood'] && empty($venue->neighborhood)) {
                $updateData['neighborhood'] = $data['neighborhood'];
            }

            // Only update cuisines if venue doesn't have cuisines or has empty cuisines
            if (! empty($data['cuisines']) && (empty($venue->cuisines) || ! is_array($venue->cuisines) || count($venue->cuisines) === 0)) {
                $updateData['cuisines'] = $data['cuisines'];
            }

            // Only update specialty if venue doesn't have specialty or has empty specialty
            if (! empty($data['specialty']) && (empty($venue->specialty) || ! is_array($venue->specialty) || count($venue->specialty) === 0)) {
                $updateData['specialty'] = $data['specialty'];
            }

            if (! empty($updateData)) {
                $venue->update($updateData);
            }
        });
    }

    private function displayVenueData(array $data, int $lineNumber): void
    {
        $this->line("Line {$lineNumber}: {$data['name']}");
        $this->line('  Address: '.($data['address'] ?? 'N/A'));
        $this->line('  Description: '.Str::limit($data['description'] ?? 'N/A', 100));
        $this->line('  Images: '.(empty($data['images']) ? 'None' : count($data['images']).' image(s)'));
        $this->line('  Cuisines: '.(empty($data['cuisines']) ? 'None' : implode(', ', $data['cuisines'])));
        $this->line('  Specialties: '.(empty($data['specialty']) ? 'None' : implode(', ', $data['specialty'])));
        $this->line('  Neighborhood: '.($data['neighborhood'] ?? 'Not determined'));
        $this->line('');
    }

    private function logError(int $lineNumber, string $message): void
    {
        $this->stats['errors']++;
        $this->errors[] = "Line {$lineNumber}: {$message}";
        $this->error("Line {$lineNumber}: {$message}");
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->info('Import Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $this->stats['processed']],
                ['Updated', $this->stats['updated']],
                ['Not Found', $this->stats['not_found']],
                ['Skipped', $this->stats['skipped']],
                ['Errors', $this->stats['errors']],
            ]
        );

        if (! empty($this->errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($this->errors as $error) {
                $this->line("  - {$error}");
            }
        }
    }
}
