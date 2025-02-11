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
use AshAllenDesign\ShortURL\Facades\ShortURL;
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
        ?VipCode $vipCode = null
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
            $data['date'].' '.$scheduleTemplate?->start_time,
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

        // Prepare meta data for non-prime bookings
        $meta = [];
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
            'schedule_template_id' => $scheduleTemplate?->id,
            'guest_count' => $data['guest_count'],
            'concierge_id' => $conciergeId,
            'status' => BookingStatus::PENDING,
            'booking_at' => $bookingAt,
            'currency' => $currency,
            'is_prime' => $isPrime,
            'vip_code_id' => $vipCode?->id,
            'meta' => $meta,
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
            $shortUrlQr = ShortURL::destinationUrl(
                route('booking.checkout', [
                    'booking' => $booking->uuid,
                    'r' => 'qr',
                ])
            )->make();

            $shortUrl = ShortURL::destinationUrl(
                route('booking.checkout', [
                    'booking' => $booking->uuid,
                    'r' => 'sms',
                ])
            )->make();

            $vipUrl = ShortURL::destinationUrl(
                route('booking.checkout', [
                    'booking' => $booking->uuid,
                    'r' => 'vip',
                ])
            )->make();
        } catch (Exception $e) {
            Sentry::captureException($e);

            $booking->delete();
            throw new RuntimeException('An error occurred while creating the booking.');
        }

        BookingCreated::dispatch($booking);

        return CreateBookingReturnData::from([
            'booking' => $booking,
            'bookingUrl' => $shortUrl->default_short_url,
            'bookingVipUrl' => $vipUrl->default_short_url,
            'qrCode' => (new QRCode)->render($shortUrlQr->default_short_url),
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
        return Auth::user()?->concierge?->id ?? config('app.house.concierge_id');
    }
}
