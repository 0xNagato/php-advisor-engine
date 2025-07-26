<?php

namespace App\Listeners;

use App\Actions\Booking\AutoApproveSmallPartyBooking;
use App\Events\BookingConfirmed;
use App\Factories\BookingPlatformFactory;
use App\Models\PlatformReservation;
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
        $anyPlatformSucceeded = false;

        // Process each platform
        foreach ($platforms as $platform) {
            $platformSuccess = false;

            try {
                switch ($platform->platform_type) {
                    case 'covermanager':
                        $platformSuccess = $this->syncToCoverManager($booking);
                        break;
                    case 'restoo':
                        $platformSuccess = $this->syncToRestoo($booking);
                        break;
                    default:
                        // Unsupported platform
                        Log::warning("Unsupported booking platform type: {$platform->platform_type}", [
                            'booking_id' => $booking->id,
                            'venue_id' => $venue->id,
                        ]);

                        continue 2; // Skip to next platform
                }

                if ($platformSuccess) {
                    $anyPlatformSucceeded = true;
                    $success = true; // Keep existing behavior for job retry logic
                }

                if (! $platformSuccess) {
                    // Removed duplicate error logging - errors are already logged at the PlatformReservation level with more context
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

        // Check for auto-approval after platform sync attempts
        if ($anyPlatformSucceeded) {
            try {
                $autoApproved = AutoApproveSmallPartyBooking::run($booking);
                if ($autoApproved) {
                    Log::info("Booking {$booking->id} was auto-approved after successful platform sync");
                }
            } catch (Throwable $e) {
                Log::error("Failed to auto-approve booking {$booking->id} after platform sync: {$e->getMessage()}", [
                    'booking_id' => $booking->id,
                    'venue_id' => $venue->id,
                    'error' => $e->getMessage(),
                ]);
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
        $platformReservation = PlatformReservation::query()
            ->where('booking_id', $booking->id)
            ->where('platform_type', 'covermanager')
            ->first() ?? PlatformReservation::createFromBooking($booking, 'covermanager');

        if (! $platformReservation) {
            return false;
        }

        return $platformReservation->syncToPlatform();
    }

    /**
     * Sync booking to Restoo
     */
    protected function syncToRestoo($booking): bool
    {
        // Create or update Restoo reservation record
        $platformReservation = PlatformReservation::query()
            ->where('booking_id', $booking->id)
            ->where('platform_type', 'restoo')
            ->first() ?? PlatformReservation::createFromBooking($booking, 'restoo');

        if (! $platformReservation) {
            return false;
        }

        return $platformReservation->syncToPlatform();
    }
}
