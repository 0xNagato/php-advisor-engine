<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\ScheduleTemplate;
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
     *
     * @returns array {booking: Booking, bookingUrl: string, qrCode: string}
     */
    public function handle(int $scheduleTemplateId, array $data, string $timezone, string $currency): array
    {
        $scheduleTemplate = ScheduleTemplate::query()->findOrFail($scheduleTemplateId);

        /** @var Carbon $bookingAt */
        $bookingAt = Carbon::createFromFormat('Y-m-d H:i:s', $data['date'].' '.$scheduleTemplate->start_time, $timezone);
        $currentDate = Carbon::now($timezone);

        throw_if(
            $bookingAt->gt($currentDate->copy()->addDays(self::MAX_DAYS_IN_ADVANCE)),
            new RuntimeException('Booking cannot be created more than '.self::MAX_DAYS_IN_ADVANCE.' days in advance.')
        );

        $booking = Booking::query()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'guest_count' => $data['guest_count'],
            'concierge_id' => Auth::user()->concierge->id,
            'status' => BookingStatus::PENDING,
            'booking_at' => $bookingAt,
            'currency' => $currency,
            'is_prime' => $scheduleTemplate->prime_time,
        ]);

        $taxData = app(SalesTaxService::class)->calculateTax(
            $booking->restaurant->region,
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
                route('bookings.create', [
                    'token' => $booking->uuid,
                    'r' => 'qr',
                ])
            )->make();

            $shortUrl = ShortURL::destinationUrl(
                route('bookings.create', [
                    'token' => $booking->uuid,
                    'r' => 'sms',
                ])
            )->make();
        } catch (Exception $e) {
            Sentry::captureException($e);

            $booking->delete();
            throw new RuntimeException('An error occurred while creating the booking.');
        }

        BookingCreated::dispatch($booking);

        return [
            'booking' => $booking,
            'bookingUrl' => $shortUrl->default_short_url,
            'qrCode' => (new QRCode())->render($shortUrlQr->default_short_url),
        ];
    }
}
