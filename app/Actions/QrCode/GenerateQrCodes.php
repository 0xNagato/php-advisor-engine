<?php

namespace App\Actions\QrCode;

use App\Models\QrCode;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateQrCodes
{
    use AsAction;

    /**
     * Generate a batch of QR codes with short URLs
     *
     * @param  int  $count  Number of QR codes to generate (default: 30)
     * @param  string  $defaultDestination  Default URL to redirect to
     * @param  string|null  $prefix  Optional prefix for the URL keys
     * @param  int|null  $referrerConciergeId  Optional referrer concierge for unassigned QR codes
     * @return Collection<QrCode> The created QrCode models
     */
    public function handle(int $count = 30, string $defaultDestination = '', ?string $prefix = null, ?int $referrerConciergeId = null): Collection
    {

        $qrCodes = collect();
        $generateQrCode = app(GenerateQrCodeWithLogo::class);

        for ($i = 0; $i < $count; $i++) {
            // Generate a unique key for this QR code
            $uniqueKey = $this->generateUniqueKey($prefix);

            // Create the QrCode record first to get the ID
            $qrCode = QrCode::query()->create([
                'url_key' => $uniqueKey,
                'name' => 'Bulk QR #'.($i + 1).' - '.$uniqueKey,
                'is_active' => true,
            ]);

            // Store referrer concierge in meta if provided
            if ($referrerConciergeId && blank($defaultDestination)) {
                $meta = $qrCode->meta ?? [];
                $meta['referrer_concierge_id'] = $referrerConciergeId;
                $qrCode->update(['meta' => $meta]);
            }

            // Determine the destination URL
            if (blank($defaultDestination)) {
                // No destination = unassigned, redirect to invitation form
                $destinationUrl = route('qr.unassigned', ['qrCode' => $qrCode->id]);
            } else {
                // Has destination URL, use it
                $destinationUrl = $defaultDestination;
            }

            // Create a short URL pointing to the destination
            $shortUrl = ShortURL::destinationUrl($destinationUrl)
                ->urlKey($uniqueKey)
                ->trackVisits()
                ->trackIPAddress()
                ->make();

            // Update the QrCode with the short URL ID
            $qrCode->update(['short_url_id' => $shortUrl->id]);

            // Now generate QR code with the PRIMA logo and QR code ID displayed
            $qrCodeData = $generateQrCode->handle($shortUrl->default_short_url, (string) $qrCode->id);

            // Update the QR code record with the path
            $qrCode->update([
                'qr_code_path' => $qrCodeData['svgPath'],
            ]);

            $qrCodes->push($qrCode->fresh());
        }

        return $qrCodes;
    }

    /**
     * Generate a unique key that hasn't been used before
     *
     * @param  string|null  $prefix  Optional prefix for the key
     * @return string The unique key
     */
    protected function generateUniqueKey(?string $prefix = null): string
    {
        $prefix = $prefix ? Str::slug($prefix).'-' : '';

        // Try to generate a unique key up to 5 times
        for ($attempts = 0; $attempts < 5; $attempts++) {
            $key = $prefix.strtolower(Str::random(6));

            // Check if this key already exists in our database
            $existingQrCode = QrCode::query()->where('url_key', $key)->exists();

            // Also check if it exists in the ShortURL database
            $existingShortUrl = \AshAllenDesign\ShortURL\Models\ShortURL::query()->where('url_key', $key)->exists();

            if (! $existingQrCode && ! $existingShortUrl) {
                return $key;
            }
        }

        // If we failed to generate a unique key after several attempts,
        // use a timestamp to ensure uniqueness
        return $prefix.strtolower(Str::random(3)).'-'.time();
    }
}
