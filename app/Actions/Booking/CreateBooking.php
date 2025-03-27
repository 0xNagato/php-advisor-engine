<?php

namespace App\Actions\Booking;

use App\Data\Booking\CreateBookingReturnData;
use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleWithBooking;
use App\Models\VipCode;
use App\Services\SalesTaxService;
use chillerlan\QRCode\QRCode;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;
use Sentry;
use Throwable;

class CreateBooking
{
    use AsAction;

    public const int MAX_DAYS_IN_ADVANCE = 30;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(
        int $scheduleTemplateId,
        array $data,
        string $timezone,
        string $currency,
        ?VipCode $vipCode = null,
        ?string $source = null,
        ?string $device = null
    ): CreateBookingReturnData {
        $scheduleTemplate = ScheduleTemplate::query()->findOrFail($scheduleTemplateId);

        // Get the schedule with override data
        $schedule = ScheduleWithBooking::query()->where('schedule_template_id', $scheduleTemplateId)
            ->where('booking_date', Carbon::parse($data['date'])->format('Y-m-d'))
            ->first();

        if (! $schedule) {
            // If no schedule view record exists, fall back to template
            $isPrime = $scheduleTemplate->prime_time;
        } else {
            $isPrime = $schedule->prime_time;
        }

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

        $conciergeId = $this->getConciergeId($vipCode);
        $concierge = Concierge::with('user')->find($conciergeId);

        // Get venue for non-prime incentive data
        $venue = $scheduleTemplate->venue;

        // Check if concierge is restricted to specific venues
        if ($concierge && $concierge->venue_group_id && filled($concierge->allowed_venue_ids)) {
            throw_unless(in_array($venue->id, $concierge->allowed_venue_ids), new RuntimeException('You are not authorized to book at this venue.'));

            throw_if($venue->venue_group_id !== $concierge->venue_group_id, new RuntimeException('You are not authorized to book at venues outside your venue group.'));
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
            $booking->total_fee,
            noTax: config('app.no_tax')
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
