<?php

namespace App\Console\Commands;

use App\Actions\GenerateVenueAgreementLink;
use App\Actions\ProcessVenueOnboarding;
use App\Models\User;
use App\Models\VenueOnboarding;
use App\Models\VenueOnboardingLocation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportIbizaVenueOnboardings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prima:import-ibiza-venues 
                            {csv : Path to the CSV file}
                            {--partner-id=27 : ID of the partner for the venues}
                            {--start=0 : Start index in the CSV (for chunking)}
                            {--count=10 : Number of venues to process per run}
                            {--output=venue_agreement_links.csv : Output CSV file for venue agreement links}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Ibiza venue onboardings from a CSV file and set them to non-prime';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $csvPath = $this->argument('csv');
        $partnerId = $this->option('partner-id');

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return 1;
        }

        // Define default booking hours (12pm to 11pm)
        $defaultBookingHours = [
            'monday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
            'tuesday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
            'wednesday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
            'thursday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
            'friday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
            'saturday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
            'sunday' => ['start' => '12:00:00', 'end' => '23:00:00', 'closed' => false],
        ];

        // Empty prime hours (everything non-prime)
        $emptyPrimeHours = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        // Get a super admin to process the onboardings
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first();

        if (!$admin) {
            $this->error("Error: Admin user not found.");
            return 1;
        }

        // Read the CSV file
        $csv = array_map('str_getcsv', file($csvPath));
        $headers = array_shift($csv); // Remove the header row

        // Group venues by contact person
        $venuesByContact = [];
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            $contactEmail = $data['Contact Email Address *'] ?? '';
            if (!$contactEmail) {
                continue;
            }
            
            if (!isset($venuesByContact[$contactEmail])) {
                $venuesByContact[$contactEmail] = [
                    'contact' => [
                        'first_name' => $data['Contact First Name *'] ?? '',
                        'last_name' => $data['Contact Last Name *'] ?? '',
                        'email' => $contactEmail,
                        'phone' => $data['Contact Phone Number *'] ?? '',
                    ],
                    'venues' => []
                ];
            }
            
            // Get region, default to 'ibiza' if not provided
            $region = strtolower($data['Region *'] ?? 'ibiza');
            
            // Add venue to the contact's list
            $venuesByContact[$contactEmail]['venues'][] = [
                'name' => $data['Venue Name *'],
                'region' => $region,
                'neighborhood' => $data['Neighborhood *'] ?? '',
                'specialties' => !empty($data['Specialties (comma-separated)']) 
                    ? array_map('trim', explode(',', $data['Specialties (comma-separated)'])) 
                    : [],
                'cuisines' => !empty($data['Cuisines (comma-separated)']) 
                    ? array_map('trim', explode(',', $data['Cuisines (comma-separated)'])) 
                    : [],
            ];
        }

        // Get the start and count parameters for chunking
        $startIndex = (int)$this->option('start');
        $count = (int)$this->option('count');
        
        // Chunk the venues by contact
        $venuesByContactArray = array_values($venuesByContact);
        $totalGroups = count($venuesByContactArray);
        
        $endIndex = min($startIndex + $count, $totalGroups);
        $chunkedVenues = array_slice($venuesByContactArray, $startIndex, $count);
        
        $this->info("Processing venues {$startIndex} to " . ($endIndex - 1) . " of {$totalGroups} total venue groups");

        // Progress bar
        $bar = $this->output->createProgressBar(count($chunkedVenues));
        $bar->start();

        // Process each venue group (by contact)
        $processedCount = 0;
        $totalVenues = 0;
        $successfulVenues = 0;
        $failedGroups = [];
        
        // Track created onboardings for agreement links
        $createdOnboardings = [];

        try {
            foreach ($chunkedVenues as $contactData) {
                $contactEmail = $contactData['contact']['email'];
                $contact = $contactData['contact'];
                $venues = $contactData['venues'];
                $totalVenues += count($venues);
                
                // Start a transaction for each venue group
                DB::beginTransaction();
                
                try {
                    // Check if user with this email already exists
                    $existingUser = User::where('email', $contact['email'])->first();
                    if ($existingUser) {
                        $this->warn("Skipping {$contactEmail} - user with email already exists");
                        // Roll back transaction since we're skipping this one
                        DB::rollBack();
                        $bar->advance();
                        continue;
                    }
                    
                    // Create venue onboarding with venue name as company name
                    $onboarding = new VenueOnboarding();
                    // Just use the first venue name as the company name
                    $venueName = $venues[0]['name'];
                    $onboarding->company_name = $venueName;
                    $onboarding->first_name = $contact['first_name'];
                    $onboarding->last_name = $contact['last_name'];
                    $onboarding->email = $contact['email'];
                    $onboarding->phone = $contact['phone'];
                    $onboarding->venue_count = count($venues);
                    $onboarding->has_logos = false;
                    $onboarding->agreement_accepted = true;
                    $onboarding->agreement_accepted_at = now();
                    $onboarding->status = 'submitted';
                    $onboarding->partner_id = $partnerId;
                    $onboarding->save();
                    
                    // Create locations for each venue
                    foreach ($venues as $venue) {
                        $location = new VenueOnboardingLocation();
                        $location->venue_onboarding_id = $onboarding->id;
                        $location->name = $venue['name'];
                        $location->region = $venue['region'];
                        $location->prime_hours = $emptyPrimeHours;
                        $location->booking_hours = $defaultBookingHours;
                        $location->use_non_prime_incentive = true;
                        $location->non_prime_per_diem = 10.0;
                        $location->save();
                    }
                    
                    // Process the onboarding to create actual venues
                    $venueDefaults = [
                        'payout_venue' => 60,
                        'booking_fee' => 200,
                    ];
                    
                    // Call the action using Laravel's app() helper
                    app(ProcessVenueOnboarding::class)->execute(
                        onboarding: $onboarding,
                        processedBy: $admin,
                        notes: 'Bulk imported from CSV on ' . date('Y-m-d'),
                        venueDefaults: $venueDefaults
                    );
                    
                    $processedCount++;
                    $successfulVenues += count($venues);
                    
                    // Store the onboarding for agreement link generation
                    $createdOnboardings[] = $onboarding;
                    
                    // Commit the transaction for this venue group
                    DB::commit();
                } catch (\Exception $e) {
                    // Roll back this venue group only
                    DB::rollBack();
                    
                    $failedGroups[] = [
                        'email' => $contactEmail,
                        'error' => $e->getMessage(),
                    ];
                    $this->error("Error processing {$contactEmail}: {$e->getMessage()}");
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine(2);
            
            // Generate CSV with venue agreement links
            if (!empty($createdOnboardings)) {
                $this->generateAgreementLinksCSV($createdOnboardings);
            }
            
            if (empty($failedGroups)) {
                $this->info("Successfully imported {$successfulVenues} venues across {$processedCount} venue groups.");
                return 0;
            } else {
                $this->info("Successfully imported {$successfulVenues} venues across {$processedCount} venue groups.");
                $this->error("Failed to process " . count($failedGroups) . " venue groups.");
                
                $this->table(
                    ['Contact Email', 'Error'],
                    collect($failedGroups)->map(function ($group) {
                        return [$group['email'], $group['error']];
                    })
                );
                
                if (count($failedGroups) == count($chunkedVenues)) {
                    return 1;
                } else {
                    return 0; // Partial success
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Fatal error: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Generate a CSV file with venue names and agreement links
     *
     * @param array $onboardings The array of created VenueOnboarding models
     * @return void
     */
    protected function generateAgreementLinksCSV(array $onboardings): void
    {
        $outputFile = $this->option('output');
        $isNewFile = !file_exists($outputFile);
        
        // Open the CSV file for appending
        $handle = fopen($outputFile, 'a');
        
        // If it's a new file, add the header row
        if ($isNewFile) {
            fputcsv($handle, ['Venue Name', 'Contact Email', 'Agreement Link']);
        }
        
        // Generate agreement links and write to CSV
        $this->info("Generating agreement links for " . count($onboardings) . " venues...");
        $progressBar = $this->output->createProgressBar(count($onboardings));
        $progressBar->start();
        
        foreach ($onboardings as $onboarding) {
            // Load locations to get all venue names
            $onboarding->load('locations');
            
            // Generate the agreement link
            try {
                $agreementLink = GenerateVenueAgreementLink::run($onboarding);
                
                // Add each venue location to the CSV
                foreach ($onboarding->locations as $location) {
                    fputcsv($handle, [
                        $location->name,
                        $onboarding->email,
                        $agreementLink
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Error generating agreement link for {$onboarding->company_name}: {$e->getMessage()}");
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        fclose($handle);
        
        $this->info("Agreement links saved to {$outputFile}");
    }
}