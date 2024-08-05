<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadVenueLogos extends Command
{
    protected $signature = 'upload:venue-logos';

    protected $description = 'Upload venue logos and update their paths in the database';

    public function handle(): void
    {
        // Define the path for the logos
        $publicPath = public_path('images/venues');

        // Get all the files in the directory
        $files = File::files($publicPath);

        $successfulUploads = 0;
        $failedUploads = [];

        foreach ($files as $file) {
            // Get the file name
            $fileName = basename($file);

            // Create the slug by replacing underscores with dashes
            $slug = str_replace('_', '-', pathinfo($fileName, PATHINFO_FILENAME));

            // Find the venue by matching the name part of the slug.
            $venues = Venue::query()->where('slug', 'like', "%-$slug")->get();

            foreach ($venues as $venue) {
                // Store the file in a public directory
                $path = Storage::disk('do')->putFileAs('venues', $file, $fileName);
                Storage::disk('do')->setVisibility($path, 'public');

                // // Update the venue's logo path
                $venue->update([
                    'venue_logo_path' => $path,
                ]);

                $this->info("Uploaded logo for $venue->name in $venue->region");
                $successfulUploads++;
            }

            if ($venues->isEmpty()) {
                $this->warn("Venue not found for logo: $fileName");
                $failedUploads[] = $fileName;
            }
        }

        $this->info("Total successful uploads: $successfulUploads");
        if (filled($failedUploads)) {
            $this->info('Failed uploads:');
            foreach ($failedUploads as $failedUpload) {
                $this->info($failedUpload);
            }
        } else {
            $this->info('All venue logos uploaded successfully!');
        }
    }
}
