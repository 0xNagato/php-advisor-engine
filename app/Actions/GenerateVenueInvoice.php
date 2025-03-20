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
        // Get the user's timezone
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

        // Parse dates in user's timezone - store them as UTC date at start/end of day
        // but maintain the actual date as selected by the user
        $startDateCarbon = Carbon::parse($startDate, $userTimezone);
        $endDateCarbon = Carbon::parse($endDate, $userTimezone);

        // Use these for date comparisons but preserve the original date components
        $startDateUtc = (clone $startDateCarbon)->startOfDay()->setTimezone('UTC');
        $endDateUtc = (clone $endDateCarbon)->endOfDay()->setTimezone('UTC');

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
            ->whereBetween('booking_at_utc', [$startDateUtc, $endDateUtc])
            ->orderBy('booking_at')
            ->get();

        throw_if($bookings->isEmpty(), new RuntimeException('No bookings found for the specified date range.'));

        // Split bookings into prime and non-prime
        $primeBookings = $bookings->where('is_prime', true);
        $nonPrimeBookings = $bookings->where('is_prime', false);

        // Calculate totals for each type
        $primeTotalAmount = $this->calculateTotal($primeBookings, $user);
        $nonPrimeTotalAmount = $this->calculateTotal($nonPrimeBookings, $user);

        return DB::transaction(function () use ($user, $startDateCarbon, $endDateCarbon, $bookings, $primeBookings, $nonPrimeBookings, $primeTotalAmount, $nonPrimeTotalAmount) {
            // Create the invoice record
            $invoice = VenueInvoice::query()->create([
                'venue_id' => $user->venue->id,
                'created_by' => auth()->user()->id,
                'start_date' => $startDateCarbon->format('Y-m-d'), // Store only the date portion
                'end_date' => $endDateCarbon->format('Y-m-d'),     // Store only the date portion
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
                    'startDate' => $startDateCarbon,
                    'endDate' => $endDateCarbon,
                    'invoiceNumber' => $invoice->invoice_number,
                    'dueDate' => $invoice->due_date,
                    'currency' => $bookings->first()?->currency ?? 'USD',
                    'invoice' => $invoice, // Pass the invoice object for Stripe URL access
                ])
                ->disk('do', 'public')
                ->save($pdfPath);

            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $pdfPath]);

            // Create Stripe invoice to get a URL for our invoice
            // This only generates a URL that will be included in our own invoice
            // It does NOT send any emails or invoices through Stripe
            if ($user->email) {
                $stripeInvoiceData = CreateStripeVenueInvoice::run(
                    $invoice,
                    app()->isProduction() ? $user->email : config('app.test_stripe_email')
                );

                if ($stripeInvoiceData) {
                    $invoice->update([
                        'stripe_invoice_id' => $stripeInvoiceData['invoice_id'],
                        'stripe_invoice_url' => $stripeInvoiceData['invoice_url'],
                    ]);

                    // Regenerate the PDF now that we have the payment link
                    pdf()
                        ->view('pdfs.venue-invoice', [
                            'venue' => $user->venue,
                            'bookings' => $bookings,
                            'primeBookings' => $primeBookings,
                            'nonPrimeBookings' => $nonPrimeBookings,
                            'primeTotalAmount' => $primeTotalAmount,
                            'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
                            'startDate' => $startDateCarbon,
                            'endDate' => $endDateCarbon,
                            'invoiceNumber' => $invoice->invoice_number,
                            'dueDate' => $invoice->due_date,
                            'currency' => $bookings->first()?->currency ?? 'USD',
                            'invoice' => $invoice, // Now includes Stripe URL
                        ])
                        ->disk('do', 'public')
                        ->save($pdfPath);
                }
            }

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

    /**
     * Prepare the data needed for the venue invoice view.
     * This can be used both by the PDF generator and the HTML preview.
     */
    public static function prepareViewData(User $user, Carbon $startDate, Carbon $endDate, VenueInvoice $invoice): array
    {
        $venue = $user->venue;

        // Prepare the date range for queries (in UTC)
        $startDateUtc = (clone $startDate)->startOfDay()->setTimezone('UTC');
        $endDateUtc = (clone $endDate)->endOfDay()->setTimezone('UTC');

        $bookings = $venue->bookings()
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
                // No venue_id in bookings table, it's accessed through schedule_templates
            ])
            ->whereBetween('booking_at_utc', [$startDateUtc, $endDateUtc])
            ->orderBy('booking_at')
            ->get();

        if ($bookings->isEmpty()) {
            throw new RuntimeException('No bookings found for the specified date range.');
        }

        // Split bookings into prime and non-prime
        $primeBookings = $bookings->where('is_prime', true);
        $nonPrimeBookings = $bookings->where('is_prime', false);

        // Use the user instance to call the calculateTotal method
        $instance = new self;
        $primeTotalAmount = $instance->calculateTotal($primeBookings, $user);
        $nonPrimeTotalAmount = $instance->calculateTotal($nonPrimeBookings, $user);

        return [
            'venue' => $venue,
            'primeBookings' => $primeBookings,
            'nonPrimeBookings' => $nonPrimeBookings,
            'primeTotalAmount' => $primeTotalAmount,
            'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'invoiceNumber' => $invoice->invoice_number,
            'invoice' => $invoice,
            'currency' => $bookings->first()?->currency ?? 'USD',
            'dueDate' => $invoice->due_date,
        ];
    }
}
