<?php

namespace App\Actions\Booking;

use App\Data\Booking\CreateBookingReturnData;
use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleWithBookingMV;
use App\Models\VipCode;
use App\Services\ReservationService;
use App\Services\SalesTaxService;
use chillerlan\QRCode\QRCode;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;
use Sentry;
use Throwable;

class CreateBooking
{
    use AsAction;

    public const int MAX_DAYS_IN_ADVANCE = 30;

    public const int MAX_TOTAL_FEE_CENTS = 50000; // 500 in any currency

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(
        int $scheduleTemplateId,
        array $data,
        ?VipCode $vipCode = null,
        ?string $source = null,
        ?string $device = null
    ): CreateBookingReturnData {
        $scheduleTemplate = ScheduleTemplate::query()->with('venue.inRegion')->findOrFail($scheduleTemplateId);
        $venue = $scheduleTemplate->venue;

        // Get the schedule with override data
        $schedule = ScheduleWithBookingMV::query()->where('schedule_template_id', $scheduleTemplateId)
            ->where('booking_date', Carbon::parse($data['date'])->format('Y-m-d'))
            ->first();

        if (! $schedule) {
            // If no schedule view record exists, fall back to the template
            $isPrime = $scheduleTemplate->prime_time;
        } else {
            $isPrime = $schedule->prime_time;
        }

        $timezone = $venue->inRegion->timezone ?? $scheduleTemplate->venue->timezone;
        $currency = $venue->inRegion->currency ?? $scheduleTemplate->venue->currency;

        /**
         * @var Carbon $bookingAt
         */
        $bookingAt = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['date'].' '.$scheduleTemplate->start_time,
            $timezone
        );
        $currentDate = Carbon::now($timezone);

        throw_if(
            $bookingAt->gt($currentDate->copy()->addDays(self::MAX_DAYS_IN_ADVANCE)),
            new RuntimeException('Booking cannot be created more than '.self::MAX_DAYS_IN_ADVANCE.' days in advance.')
        );

        // Validate booking time restrictions for today's bookings
        if ($bookingAt->isToday()) {
            // Check minimum advance booking time (global setting)
            $minAdvanceTime = $currentDate->copy()->addMinutes(ReservationService::MINUTES_PAST);
            throw_if(
                $bookingAt->lt($minAdvanceTime),
                new RuntimeException('Booking cannot be created less than '.ReservationService::MINUTES_PAST.' minutes in advance for today.')
            );

            // Check venue's specific cutoff time if set
            if ($venue->cutoff_time) {
                /*
                 * This is a complete rewrite of the venue cutoff time logic to fix timezone issues.
                 * The issue was that comparing dates with different timezones leads to incorrect results.
                 */

                // Step 1: Get the venue's timezone
                $venueTimezone = $venue->timezone;

                // Step 2: Get current time in the venue's timezone
                $currentTime = Carbon::now($venueTimezone);

                // Step 3: Extract just the time portion (not the date) from the venue's cutoff time
                $cutoffHour = (int) $venue->cutoff_time->format('H');
                $cutoffMinute = (int) $venue->cutoff_time->format('i');
                $cutoffSecond = (int) $venue->cutoff_time->format('s');

                // Step 4: Create today's cutoff time in the venue timezone
                $todayCutoffTime = Carbon::now($venueTimezone)
                    ->setTime($cutoffHour, $cutoffMinute, $cutoffSecond);

                // Step 5: Compare current time with cutoff time (both in venue timezone)
                $isPastCutoff = $currentTime->greaterThan($todayCutoffTime);

                // Detailed logging to verify times are created and compared correctly
                Log::debug('Cutoff time check', [
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'current_time' => $currentTime->toDateTimeString(),
                    'current_time_timezone' => $currentTime->timezone->getName(),
                    'cutoff_time' => $todayCutoffTime->toDateTimeString(),
                    'cutoff_time_timezone' => $todayCutoffTime->timezone->getName(),
                    'cutoff_raw' => sprintf('%02d:%02d:%02d', $cutoffHour, $cutoffMinute, $cutoffSecond),
                    'is_past_cutoff' => $isPastCutoff,
                ]);

                throw_if(
                    $isPastCutoff,
                    new RuntimeException('Booking cannot be created after the venue\'s cutoff time for today.')
                );
            }
        }

        $conciergeId = $this->getConciergeId($vipCode);
        $concierge = Concierge::with('user')->find($conciergeId);

        // Get venue for non-prime incentive data
        $venue = $scheduleTemplate->venue;

        // Check if concierge is restricted to specific venues
        if ($concierge && $concierge->venue_group_id && filled($concierge->allowed_venue_ids)) {
            throw_unless(
                in_array($venue->id, $concierge->allowed_venue_ids),
                new RuntimeException('You are not authorized to book at this venue.')
            );

            throw_if(
                $venue->venue_group_id !== $concierge->venue_group_id,
                new RuntimeException('You are not authorized to book at venues outside your venue group.')
            );
        }

        // Prepare meta data for non-prime bookings
        $meta = [];
        if ($source) {
            $meta['source'] = $source;
        }
        if ($device) {
            $meta['device'] = $device;
        }
        $meta['venue'] = [
            'id' => $venue->id,
            'name' => $venue->name,
        ];
        $meta['concierge'] = [
            'id' => $conciergeId,
            'name' => $concierge->user->name ?? 'Unknown',
            'hotel_name' => $concierge->hotel_name ?? 'Unknown',
        ];
        if (! $scheduleTemplate->prime_time) {
            $meta['non_prime_incentive'] = [
                'fee_per_head' => $venue->non_prime_fee_per_head,
                'type' => $venue->non_prime_type,
                'created_at' => now()->toDateTimeString(),
            ];
        }

        $booking = Booking::query()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_count' => $data['guest_count'],
            'concierge_id' => $conciergeId,
            'status' => BookingStatus::PENDING,
            'booking_at' => $bookingAt,
            'currency' => $currency,
            'is_prime' => $isPrime,
            'vip_code_id' => $vipCode?->id,
            'meta' => $meta,
            'source' => $source,
            'device' => $device,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax(
            $booking->venue->region,
            $booking->total_fee
        );

        $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

        $booking->update([
            'tax' => $taxData->tax,
            'tax_amount_in_cents' => $taxData->amountInCents,
            'city' => $taxData->region,
            'total_with_tax_in_cents' => $totalWithTaxInCents,
        ]);

        try {
            $shortUrlQr = route('booking.checkout', [
                'booking' => $booking->uuid,
                'r' => 'qr',
            ]);

            $shortUrl = route('booking.checkout', [
                'booking' => $booking->uuid,
                'r' => 'sms',
            ]);

            $vipUrl = route('booking.checkout', [
                'booking' => $booking->uuid,
                'r' => 'vip',
            ]);

            $qrCode = (new QRCode)->render($shortUrlQr);
        } catch (Exception $e) {
            Sentry::captureException($e);

            $booking->delete();
            throw new RuntimeException('An error occurred while creating the booking.');
        }

        BookingCreated::dispatch($booking);

        // Dispatch the job to handle refreshing
        //        RefreshMaterializedView::dispatch();

        return CreateBookingReturnData::from([
            'booking' => $booking,
            'bookingUrl' => $shortUrl,
            'bookingVipUrl' => $vipUrl,
            'qrCode' => $qrCode,
        ]);
    }

    private function getConciergeId(?VipCode $vipCode = null)
    {
        // First priority: VIP check
        if ($vipCode) {
            return $vipCode->concierge_id;
        }

        // Second priority: Partner check
        if (auth()->user()->hasActiveRole('partner')) {
            return config('app.house.concierge_id');
        }

        // Default: Regular concierge
        return Auth::user()->concierge->id ?? config('app.house.concierge_id');
    }
}
