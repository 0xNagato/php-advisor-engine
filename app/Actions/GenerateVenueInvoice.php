<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EarningType;
use App\Enums\VenueInvoiceStatus;
use App\Models\User;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

use function Spatie\LaravelPdf\Support\pdf;

class GenerateVenueInvoice
{
    use AsAction;

    public function handle(User $user, string $startDate, string $endDate): VenueInvoice
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

        throw_if($bookings->isEmpty(), new RuntimeException('No bookings found for the specified date range.'));

        // Split bookings into prime and non-prime
        $primeBookings = $bookings->where('is_prime', true);
        $nonPrimeBookings = $bookings->where('is_prime', false);

        // Calculate totals for each type
        $primeTotalAmount = $this->calculateTotal($primeBookings, $user);
        $nonPrimeTotalAmount = $this->calculateTotal($nonPrimeBookings, $user);

        return DB::transaction(function () use ($user, $startDate, $endDate, $bookings, $primeBookings, $nonPrimeBookings, $primeTotalAmount, $nonPrimeTotalAmount) {
            // Create the invoice record
            $invoice = VenueInvoice::query()->create([
                'venue_id' => $user->venue->id,
                'created_by' => auth()->user()->id,
                'invoice_number' => VenueInvoice::generateInvoiceNumber($user->venue),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'prime_total' => $primeTotalAmount,
                'non_prime_total' => $nonPrimeTotalAmount,
                'total_amount' => $primeTotalAmount + $nonPrimeTotalAmount,
                'currency' => $bookings->first()?->currency ?? 'USD',
                'due_date' => now()->addDays(15),
                'status' => VenueInvoiceStatus::DRAFT,
                'booking_ids' => $bookings->pluck('id')->toArray(),
            ]);

            // Generate and store PDF
            $pdfPath = config('app.env').'/venue-invoices/'.$invoice->name().'.pdf';

            pdf()
                ->view('pdfs.venue-invoice', [
                    'venue' => $user->venue,
                    'bookings' => $bookings,
                    'primeBookings' => $primeBookings,
                    'nonPrimeBookings' => $nonPrimeBookings,
                    'primeTotalAmount' => $primeTotalAmount,
                    'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'invoiceNumber' => $invoice->invoice_number,
                    'dueDate' => $invoice->due_date,
                ])
                ->disk('do', 'public')
                ->save($pdfPath);

            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $pdfPath]);

            return $invoice;
        });
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
