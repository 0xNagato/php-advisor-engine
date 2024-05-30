<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadRestaurantLogos extends Command
{
    protected $signature = 'upload:restaurant-logos';

    protected $description = 'Upload restaurant logos and update their paths in the database';

    public function handle(): void
    {
        // Define the path for the logos
        $publicPath = public_path('images/restaurants');

        // Get all the files in the directory
        $files = File::files($publicPath);

        $successfulUploads = 0;
        $failedUploads = [];

        foreach ($files as $file) {
            // Get the file name
            $fileName = basename($file);

            // Create the slug by replacing underscores with dashes
            $slug = str_replace('_', '-', pathinfo($fileName, PATHINFO_FILENAME));

            // Find the restaurant by matching the name part of the slug.
            $restaurants = Restaurant::query()->where('slug', 'like', "%-$slug")->get();

            foreach ($restaurants as $restaurant) {
                // Store the file in a public directory
                $path = Storage::disk('do')->putFileAs('restaurants', $file, $fileName);
                Storage::disk('do')->setVisibility($path, 'public');

                // // Update the restaurant's logo path
                $restaurant->update([
                    'restaurant_logo_path' => $path,
                ]);

                $this->info("Uploaded logo for $restaurant->restaurant_name in $restaurant->region");
                $successfulUploads++;
            }

            if ($restaurants->isEmpty()) {
                $this->warn("Restaurant not found for logo: $fileName");
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
            $this->info('All restaurant logos uploaded successfully!');
        }
    }
}
