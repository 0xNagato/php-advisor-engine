<?php

namespace App\Services;

use App\Models\BookingModificationRequest;
use App\Notifications\Booking\ConciergeModificationApproved;
use App\Notifications\Booking\ConciergeModificationRejected;
use App\Notifications\Booking\CustomerModificationApproved;
use App\Notifications\Booking\CustomerModificationRejected;
use App\Services\Booking\BookingCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingModificationService
{
    /**
     * Approve the given booking modification request.
     *
     * @throws Throwable
     */
    public function approve(BookingModificationRequest $modificationRequest, string $role = 'Venue'): void
    {
        DB::transaction(function () use ($modificationRequest, $role) {
            $modificationRequest->markAsApproved();

            // Delete existing earnings before updating booking
            $modificationRequest->booking->earnings()->delete();

            $newBookingAt = $modificationRequest->booking->booking_at->format('Y-m-d').' '.
                $modificationRequest->requested_time;

            $booking_at_utc = Carbon::parse(
                $newBookingAt,
                $modificationRequest->booking->venue->timezone
            )->setTimezone('UTC');

            DB::table('bookings')
                ->where('id', $modificationRequest->booking->id)
                ->update([
                    'guest_count' => $modificationRequest->requested_guest_count,
                    'schedule_template_id' => $modificationRequest->requested_schedule_template_id,
                    'booking_at' => $newBookingAt,
                    'booking_at_utc' => $booking_at_utc,
                ]);

            $refreshedBooking = $modificationRequest->booking->refresh();

            app(BookingCalculationService::class)->calculateEarnings($refreshedBooking);

            $modificationRequest->notify(new CustomerModificationApproved);
            $modificationRequest->notify(new ConciergeModificationApproved);

            activity()
                ->performedOn($modificationRequest->booking)
                ->withProperties([
                    'modification_request_id' => $modificationRequest->id,
                    'status' => 'approved',
                ])
                ->log("$role approved booking modification request");
        });
    }

    /**
     * Reject the given booking modification request.
     */
    public function reject(
        BookingModificationRequest $modificationRequest,
        ?string $reason,
        string $role = 'Venue'
    ): void {
        $modificationRequest->markAsRejected($reason);

        $modificationRequest->notify(new CustomerModificationRejected);
        $modificationRequest->notify(new ConciergeModificationRejected);

        activity()
            ->performedOn($modificationRequest->booking)
            ->withProperties([
                'modification_request_id' => $modificationRequest->id,
                'status' => 'rejected',
                'reason' => $reason,
            ])
            ->log("$role rejected booking modification request");
    }
}
