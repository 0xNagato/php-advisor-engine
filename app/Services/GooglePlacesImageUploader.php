<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GooglePlacesImageUploader
{
    private string $disk = 'do';

    private readonly string $directory;

    public function __construct()
    {
        $this->directory = app()->environment().'/venues/images';
    }

    /**
     * Download and upload Google Places photos to Digital Ocean
     *
     * @param  array  $photoUrls  Array of Google Places photo URLs
     * @param  string  $venueSlug  Venue slug for naming files
     * @return array Array of uploaded file paths
     */
    public function uploadGooglePhotos(array $photoUrls, string $venueSlug): array
    {
        $uploadedPaths = [];

        foreach ($photoUrls as $photoUrl) {
            try {
                $path = $this->downloadAndUploadPhoto($photoUrl, $venueSlug);
                if ($path) {
                    $uploadedPaths[] = $path;
                }
            } catch (Exception $e) {
                Log::warning('Failed to upload Google Places photo', [
                    'photo_url' => $photoUrl,
                    'venue_slug' => $venueSlug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $uploadedPaths;
    }

    /**
     * Download a single photo and upload it to Digital Ocean
     *
     * @param  string  $photoUrl  Google Places photo URL
     * @param  string  $venueSlug  Venue slug for naming
     * @return string|null The uploaded file path or null on failure
     */
    private function downloadAndUploadPhoto(string $photoUrl, string $venueSlug): ?string
    {
        try {
            // Download the image
            $response = Http::timeout(30)->get($photoUrl);

            throw_unless($response->successful(), new Exception("Failed to download photo: HTTP {$response->status()}"));

            // Get image content
            $imageContent = $response->body();
            throw_if(empty($imageContent), new Exception('Downloaded image is empty'));

            // Determine file extension from content type
            $extension = $this->getExtensionFromResponse($response);
            throw_unless($extension, new Exception('Could not determine image format'));

            // Generate unique filename
            $filename = $venueSlug.'-google-'.time().'-'.Str::random(6).'.'.$extension;
            $path = $this->directory.'/'.$filename;

            // Upload to Digital Ocean
            $disk = Storage::disk($this->disk);

            throw_unless($disk->put($path, $imageContent), new Exception('Failed to upload to Digital Ocean'));

            // Set public visibility
            $disk->setVisibility($path, 'public');

            Log::info('Successfully uploaded Google Places photo', [
                'original_url' => $photoUrl,
                'uploaded_path' => $path,
                'venue_slug' => $venueSlug,
            ]);

            return $path;
        } catch (Exception $e) {
            Log::error('Failed to download and upload Google Places photo', [
                'photo_url' => $photoUrl,
                'venue_slug' => $venueSlug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get file extension from HTTP response
     */
    private function getExtensionFromResponse(Response $response): ?string
    {
        $contentType = $response->header('content-type');

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg', // Default to jpg for unknown types
        };
    }
}
