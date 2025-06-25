<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Factories\BookingPlatformFactory;
use App\Models\CoverManagerReservation;
use App\Models\RestooReservation;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingPlatformSyncListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct(protected BookingPlatformFactory $platformFactory)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $venue = $booking->venue;

        if (! $venue) {
            return;
        }

        // Get the venue's platform configuration
        $platforms = $venue->platforms()->where('is_enabled', true)->get();

        if ($platforms->isEmpty()) {
            return;
        }

        $success = false;

        // Process each platform
        foreach ($platforms as $platform) {
            try {
                switch ($platform->platform_type) {
                    case 'covermanager':
                        $success = $this->syncToCoverManager($booking);
                        break;
                    case 'restoo':
                        $success = $this->syncToRestoo($booking);
                        break;
                    default:
                        // Unsupported platform
                        Log::warning("Unsupported booking platform type: {$platform->platform_type}", [
                            'booking_id' => $booking->id,
                            'venue_id' => $venue->id,
                        ]);

                        continue 2; // Skip to next platform
                }

                if (! $success) {
                    Log::error("Failed to sync booking {$booking->id} to {$platform->platform_type}", [
                        'booking_id' => $booking->id,
                        'venue_id' => $venue->id,
                        'venue_name' => $venue->name,
                        'platform' => $platform->platform_type,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error("Exception syncing booking {$booking->id} to {$platform->platform_type}", [
                    'booking_id' => $booking->id,
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'platform' => $platform->platform_type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Only retry for certain exceptions
                if ($e instanceof ConnectException || $e instanceof ServerException) {
                    $this->release(600); // 10 minutes

                    return;
                }
            }
        }

        // If all platforms failed, release the job to try again later
        if (! $success) {
            $this->release(300); // 5 minutes
        }
    }

    /**
     * Sync booking to CoverManager
     */
    protected function syncToCoverManager($booking): bool
    {
        // Create or update CoverManager reservation record
        $coverManagerReservation = CoverManagerReservation::query()
            ->where('booking_id', $booking->id)
            ->first() ?? CoverManagerReservation::createFromBooking($booking);

        if (! $coverManagerReservation) {
            return false;
        }

        return $coverManagerReservation->syncToCoverManager();
    }

    /**
     * Sync booking to Restoo
     */
    protected function syncToRestoo($booking): bool
    {
        // Create or update Restoo reservation record
        $restooReservation = RestooReservation::query()
            ->where('booking_id', $booking->id)
            ->first() ?? RestooReservation::createFromBooking($booking);

        if (! $restooReservation) {
            return false;
        }

        return $restooReservation->syncToRestoo();
    }
}
