<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ScheduleTemplate;
use App\Services\SalesTaxService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateBooking
{
    use AsAction;

    public function handle(int $scheduleTemplateId, array $data, string $timezone, string $currency): Booking
    {
        $scheduleTemplate = ScheduleTemplate::query()->findOrFail($scheduleTemplateId);
        $bookingAt = Carbon::createFromFormat('Y-m-d H:i:s', $data['date'].' '.$scheduleTemplate->start_time, $timezone);

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

        return $booking;
    }
}
