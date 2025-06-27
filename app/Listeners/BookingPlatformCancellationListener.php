<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Factories\BookingPlatformFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingPlatformCancellationListener implements ShouldQueue
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
    public function handle(BookingCancelled $event): void
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

        // Process each platform
        foreach ($platforms as $platform) {
            try {
                switch ($platform->platform_type) {
                    case 'covermanager':
                        $this->cancelInCoverManager($booking);
                        break;
                    case 'restoo':
                        $this->cancelInRestoo($booking);
                        break;
                    default:
                        // Unsupported platform
                        Log::warning("Unsupported booking platform type for cancellation: {$platform->platform_type}", [
                            'booking_id' => $booking->id,
                            'venue_id' => $venue->id,
                        ]);

                        continue 2; // Skip to next platform
                }
            } catch (Throwable $e) {
                Log::error("Exception cancelling booking {$booking->id} in {$platform->platform_type}", [
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
                } else {
                    $this->fail($e);
                }
            }
        }
    }

    /**
     * Cancel booking in CoverManager
     */
    protected function cancelInCoverManager($booking): bool
    {
        // Find associated CoverManager reservation
        $reservation = PlatformReservation::query()
            ->where('booking_id', $booking->id)
            ->where('platform_type', 'covermanager')
            ->first();

        if (! $reservation) {
            Log::info("No CoverManager reservation found for booking $booking->id to cancel");

            return true; // Nothing to cancel
        }

        // Cancel booking in CoverManager
        $result = $reservation->cancelInPlatform();

        if (! $result) {
            // Release the job to try again later
            $this->release(300); // 5 minutes

            return false;
        } else {
            Log::info("Successfully cancelled booking $booking->id in CoverManager", [
                'booking_id' => $booking->id,
                'reservation_id' => $reservation->id,
                'platform_reservation_id' => $reservation->platform_reservation_id,
            ]);

            return true;
        }
    }

    /**
     * Cancel booking in Restoo
     */
    protected function cancelInRestoo($booking): bool
    {
        // Find associated Restoo reservation
        $reservation = PlatformReservation::query()
            ->where('booking_id', $booking->id)
            ->where('platform_type', 'restoo')
            ->first();

        if (! $reservation) {
            Log::info("No Restoo reservation found for booking $booking->id to cancel");

            return true; // Nothing to cancel
        }

        // Cancel booking in Restoo
        $result = $reservation->cancelInPlatform();

        if (! $result) {
            // Release the job to try again later
            $this->release(300); // 5 minutes

            return false;
        } else {
            Log::info("Successfully cancelled booking $booking->id in Restoo", [
                'booking_id' => $booking->id,
                'reservation_id' => $reservation->id,
                'platform_reservation_id' => $reservation->platform_reservation_id,
            ]);

            return true;
        }
    }
}
