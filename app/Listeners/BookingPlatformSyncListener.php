<?php

namespace App\Listeners;

use App\Actions\Booking\AutoApproveSmallPartyBooking;
use App\Actions\Booking\SendConfirmationToVenueContacts;
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

        // Check if we should simulate platform sync success in development
        if (config('app.simulate_platform_sync_success')) {
            Log::info("Simulating platform sync success for booking {$booking->id} (development mode)");
            $success = true;
            $anyPlatformSucceeded = true;
        } else {
            // Process each platform normally
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
        }

        // Handle SMS notifications after platform sync attempts
        if (AutoApproveSmallPartyBooking::qualifiesForAutoApproval($booking)) {
            if ($anyPlatformSucceeded) {
                // Platform sync succeeded - try auto-approval
                try {
                    $autoApproved = AutoApproveSmallPartyBooking::run($booking);
                    if ($autoApproved) {
                        Log::info("Booking {$booking->id} was auto-approved after successful platform sync");
                        // Auto-approval notification is sent by AutoApproveSmallPartyBooking action
                    } else {
                        // Auto-approval failed despite platform success - send regular confirmation
                        Log::info("Auto-approval failed for booking {$booking->id} despite platform sync success - sending regular confirmation SMS");
                        SendConfirmationToVenueContacts::run($booking);
                    }
                } catch (Throwable $e) {
                    Log::error("Failed to auto-approve booking {$booking->id} after platform sync: {$e->getMessage()}", [
                        'booking_id' => $booking->id,
                        'venue_id' => $venue->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Auto-approval failed - send regular confirmation SMS
                    SendConfirmationToVenueContacts::run($booking);
                }
            } else {
                // Platform sync failed for auto-approval eligible booking - send regular confirmation
                Log::info("Platform sync failed for auto-approval eligible booking {$booking->id} - sending regular confirmation SMS");
                SendConfirmationToVenueContacts::run($booking);
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
        // Check if reservation already exists
        $platformReservation = PlatformReservation::query()
            ->where('booking_id', $booking->id)
            ->where('platform_type', 'covermanager')
            ->first();

        if ($platformReservation) {
            // If already exists, only sync if not already synced
            return $platformReservation->syncToPlatform();
        }

        // Create new reservation (this calls the API once)
        $platformReservation = PlatformReservation::createFromBooking($booking, 'covermanager');

        // Return success based on whether reservation was created
        return $platformReservation !== null;
    }

    /**
     * Sync booking to Restoo
     */
    protected function syncToRestoo($booking): bool
    {
        // Check if reservation already exists
        $platformReservation = PlatformReservation::query()
            ->where('booking_id', $booking->id)
            ->where('platform_type', 'restoo')
            ->first();

        if ($platformReservation) {
            // If already exists, only sync if not already synced
            return $platformReservation->syncToPlatform();
        }

        // Create new reservation (this calls the API once)
        $platformReservation = PlatformReservation::createFromBooking($booking, 'restoo');

        // Return success based on whether reservation was created
        return $platformReservation !== null;
    }
}
