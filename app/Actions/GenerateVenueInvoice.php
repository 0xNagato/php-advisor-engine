<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EarningType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelPdf\PdfBuilder;
use Str;

use function Spatie\LaravelPdf\Support\pdf;

class GenerateVenueInvoice
{
    use AsAction;

    public function handle(User $user, string $startDate, string $endDate): PdfBuilder
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $bookings = $user->venue->bookings()
            ->with(['earnings' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->where('type', EarningType::VENUE_PAID->value)
                            ->orWhere('type', EarningType::VENUE->value);
                    });
            }, 'schedule'])
            ->select([
                'bookings.id',
                'bookings.booking_at',
                'bookings.guest_count',
                'bookings.currency',
                'bookings.schedule_template_id',
                'bookings.guest_first_name',
                'bookings.guest_last_name',
                'bookings.guest_phone',
                'bookings.guest_email',
                'bookings.is_prime',
            ])
            ->whereBetween('confirmed_at', [$startDate, $endDate])
            ->orderBy('booking_at')
            ->get();

        // Split bookings into prime and non-prime
        $primeBookings = $bookings->where('is_prime', true);
        $nonPrimeBookings = $bookings->where('is_prime', false);

        $path = config('app.env').'/venue-invoices/prima-invoice-'.Str::slug($user->venue->name).'-'.$startDate->format('Ymd').'-'.$endDate->format('Ymd').'.pdf';

        // Calculate totals for each type
        $primeTotalAmount = $this->calculateTotal($primeBookings, $user);
        $nonPrimeTotalAmount = $this->calculateTotal($nonPrimeBookings, $user);

        return pdf()
            ->view('pdfs.venue-invoice', [
                'venue' => $user->venue,
                'bookings' => $bookings,
                'primeBookings' => $primeBookings,
                'nonPrimeBookings' => $nonPrimeBookings,
                'primeTotalAmount' => $primeTotalAmount,
                'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'invoiceNumber' => $startDate->format('Ymd').'-'.$endDate->format('Ymd').'-'.$user->id,
                'dueDate' => now()->addDays(15),
            ])
            ->disk('do', 'public')
            ->save($path);
    }

    private function calculateTotal(Collection $bookings, User $user): float
    {
        return $bookings->sum(function ($booking) use ($user) {
            if ($booking->is_prime) {
                // For prime bookings, we pay the venue (positive amount)
                return abs($booking->earnings
                    ->where('user_id', $user->id)
                    ->where('type', EarningType::VENUE->value)
                    ->sum('amount'));
            } else {
                // For non-prime bookings, venue pays us (negative amount)
                return -abs($booking->earnings
                    ->where('user_id', $user->id)
                    ->where('type', EarningType::VENUE_PAID->value)
                    ->sum('amount'));
            }
        });
    }
}
