<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\EarningType;
use App\Enums\VenueInvoiceStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\VenueGroup;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

use function Spatie\LaravelPdf\Support\pdf;

class GenerateVenueGroupInvoice
{
    use AsAction;

    public function handle(VenueGroup $venueGroup, string $startDate, string $endDate): VenueInvoice
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

        // Get all venues in the group
        $venues = $venueGroup->venues;

        throw_if($venues->isEmpty(), new RuntimeException('No venues found in this venue group.'));

        /** @var User $primaryManager */
        $primaryManager = $venueGroup->primaryManager;
        throw_if(! $primaryManager, new RuntimeException('Venue group does not have a primary manager.'));

        // Get the first venue to use as a reference venue
        $referenceVenue = $venues->first();

        $allBookings = collect();
        $venuesData = [];
        $bookingsByVenue = [];
        $primeTotalAmount = 0;
        $nonPrimeTotalAmount = 0;
        $currency = 'USD'; // Default fallback currency

        // Process each venue
        foreach ($venues as $venue) {
            // Get bookings for this venue through the HasManyThrough relationship
            $bookings = $venue->bookings()
                ->with(['earnings' => function ($query) use ($primaryManager) {
                    // Only get earnings for the primary manager
                    $query->where('user_id', $primaryManager->id)
                        ->where(function ($q) {
                            $q->where('type', EarningType::VENUE_PAID->value)
                                ->orWhere('type', EarningType::VENUE->value);
                        });
                }, 'schedule'])
                ->select([
                    'bookings.id',
                    'bookings.booking_at',
                    'bookings.booking_at_utc',
                    'bookings.guest_count',
                    'bookings.currency',
                    'bookings.schedule_template_id',
                    'bookings.guest_first_name',
                    'bookings.guest_last_name',
                    'bookings.guest_phone',
                    'bookings.guest_email',
                    'bookings.is_prime',
                    'bookings.status',
                ])
                ->whereBetween('booking_at_utc', [$startDateUtc, $endDateUtc])
                ->whereIn('bookings.status', BookingStatus::PAYOUT_STATUSES)
                ->orderBy('booking_at')
                ->get();

            if ($bookings->isEmpty()) {
                continue;
            }

            // Store the bookings for this venue
            $bookingsByVenue[$venue->id] = [
                'venue' => $venue,
                'bookings' => $bookings,
                'primeBookings' => $bookings->where('is_prime', true),
                'nonPrimeBookings' => $bookings->where('is_prime', false),
            ];

            // Set currency from first venue with bookings
            if ($bookings->isNotEmpty()) {
                $currency = $bookings->first()->currency ?? $currency;
            }

            // Split bookings into prime and non-prime
            $primeBookings = $bookings->where('is_prime', true);
            $nonPrimeBookings = $bookings->where('is_prime', false);

            // Calculate totals for each type - using the same calculation method from GenerateVenueInvoice
            $venuePrimeTotal = $this->calculateTotal($primeBookings, $primaryManager);
            $venueNonPrimeTotal = $this->calculateTotal($nonPrimeBookings, $primaryManager);

            // Add to aggregate totals
            $primeTotalAmount += $venuePrimeTotal;
            $nonPrimeTotalAmount += $venueNonPrimeTotal;

            // Store venue data
            $venuesData[] = [
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'prime_total' => $venuePrimeTotal,
                'non_prime_total' => $venueNonPrimeTotal,
                'total_amount' => $venuePrimeTotal + $venueNonPrimeTotal,
                'booking_ids' => $bookings->pluck('id')->toArray(),
                'prime_bookings_count' => $primeBookings->count(),
                'non_prime_bookings_count' => $nonPrimeBookings->count(),
                'total_guests' => $bookings->sum('guest_count'),
            ];

            // Add to all bookings collection
            $allBookings = $allBookings->merge($bookings);
        }

        throw_if($allBookings->isEmpty(), new RuntimeException('No bookings eligible for payout found for any venue in the specified date range.'));

        return DB::transaction(function () use ($venueGroup, $referenceVenue, $startDateCarbon, $endDateCarbon, $allBookings, $bookingsByVenue, $primeTotalAmount, $nonPrimeTotalAmount, $venuesData, $currency) {
            // Create the invoice record - using only the fields from the latest version
            $invoice = VenueInvoice::query()->create([
                'venue_id' => $referenceVenue->id, // Use the first venue's ID
                'venue_group_id' => $venueGroup->id,
                'created_by' => auth()->user()->id,
                'start_date' => $startDateCarbon->format('Y-m-d'), // Store only the date portion
                'end_date' => $endDateCarbon->format('Y-m-d'),     // Store only the date portion
                'prime_total' => $primeTotalAmount,
                'non_prime_total' => $nonPrimeTotalAmount,
                'total_amount' => $primeTotalAmount + $nonPrimeTotalAmount,
                'currency' => $currency,
                'due_date' => now()->addDays(15),
                'status' => VenueInvoiceStatus::DRAFT,
                'booking_ids' => $allBookings->pluck('id')->toArray(),
                'venues_data' => $venuesData,
            ]);

            // Generate and store PDF
            $pdfPath = config('app.env').'/venue-invoices/'.$invoice->name().'.pdf';

            pdf()
                ->view('pdfs.venue-group-invoice', [
                    'venue' => $referenceVenue, // Include a reference venue for compatibility
                    'venueGroup' => $venueGroup,
                    'bookings' => $allBookings,
                    'bookingsByVenue' => $bookingsByVenue,
                    'venuesData' => $venuesData,
                    'primeTotalAmount' => $primeTotalAmount,
                    'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
                    'startDate' => $startDateCarbon,
                    'endDate' => $endDateCarbon,
                    'invoiceNumber' => $invoice->invoice_number,
                    'dueDate' => $invoice->due_date,
                    'currency' => $currency,
                    'invoice' => $invoice, // Pass the invoice object for Stripe URL access
                ])
                ->disk('do', 'public')
                ->save($pdfPath);

            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $pdfPath]);

            // Create Stripe invoice to get a URL for our invoice
            // This only generates a URL that will be included in our own invoice
            // It does NOT send any emails or invoices through Stripe
            /** @var User $primaryManager */
            $primaryManager = $venueGroup->primaryManager;
            if ($primaryManager && $primaryManager->email) {
                $stripeInvoiceData = CreateStripeVenueInvoice::run(
                    $invoice,
                    app()->isProduction() ? $primaryManager->email : config('app.test_stripe_email')
                );

                if ($stripeInvoiceData) {
                    $invoice->update([
                        'stripe_invoice_id' => $stripeInvoiceData['invoice_id'],
                        'stripe_invoice_url' => $stripeInvoiceData['invoice_url'],
                    ]);

                    // Regenerate the PDF now that we have the payment link
                    pdf()
                        ->view('pdfs.venue-group-invoice', [
                            'venue' => $referenceVenue,
                            'venueGroup' => $venueGroup,
                            'bookings' => $allBookings,
                            'bookingsByVenue' => $bookingsByVenue,
                            'venuesData' => $venuesData,
                            'primeTotalAmount' => $primeTotalAmount,
                            'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
                            'startDate' => $startDateCarbon,
                            'endDate' => $endDateCarbon,
                            'invoiceNumber' => $invoice->invoice_number,
                            'dueDate' => $invoice->due_date,
                            'currency' => $currency,
                            'invoice' => $invoice,
                        ])
                        ->disk('do', 'public')
                        ->save($pdfPath);
                }
            }

            return $invoice;
        });
    }

    /**
     * Calculate the total amount for a collection of bookings for a specific user
     *
     * @param  Collection<Booking>  $bookings
     */
    private function calculateTotal(Collection $bookings, User $user): float
    {
        return $bookings->sum(function ($booking) use ($user) {
            if ($booking->is_prime) {
                // For prime bookings, we pay the venue (positive amount)
                // Use EXACTLY the same calculation as GenerateVenueInvoice
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
     * Prepare the data needed for the venue group invoice view.
     * This can be used both by the PDF generator and the HTML preview.
     */
    public static function prepareViewData(VenueGroup $venueGroup, Carbon $startDate, Carbon $endDate, VenueInvoice $invoice): array
    {
        // Get all venues in the group
        $venues = $venueGroup->venues;

        throw_if($venues->isEmpty(), new RuntimeException('No venues found in this venue group.'));

        /** @var User $primaryManager */
        $primaryManager = $venueGroup->primaryManager;
        throw_unless($primaryManager, new RuntimeException('Venue group does not have a primary manager.'));

        // Get the first venue to use as a reference venue
        $referenceVenue = $venues->first();

        // Prepare the date range for queries (in UTC)
        $startDateUtc = (clone $startDate)->startOfDay()->setTimezone('UTC');
        $endDateUtc = (clone $endDate)->endOfDay()->setTimezone('UTC');

        $allBookings = collect();
        $venuesData = [];
        $bookingsByVenue = [];
        $primeTotalAmount = 0;
        $nonPrimeTotalAmount = 0;
        $currency = 'USD'; // Default fallback currency

        // Process each venue
        foreach ($venues as $venue) {
            // Get bookings for this venue through the HasManyThrough relationship
            $bookings = $venue->bookings()
                ->with(['earnings' => function ($query) use ($primaryManager) {
                    // Only get earnings for the primary manager
                    $query->where('user_id', $primaryManager->id)
                        ->where(function ($q) {
                            $q->where('type', EarningType::VENUE_PAID->value)
                                ->orWhere('type', EarningType::VENUE->value);
                        });
                }, 'schedule'])
                ->select([
                    'bookings.id',
                    'bookings.booking_at',
                    'bookings.booking_at_utc',
                    'bookings.guest_count',
                    'bookings.currency',
                    'bookings.schedule_template_id',
                    'bookings.guest_first_name',
                    'bookings.guest_last_name',
                    'bookings.guest_phone',
                    'bookings.guest_email',
                    'bookings.is_prime',
                    'bookings.status',
                ])
                ->whereBetween('booking_at_utc', [$startDateUtc, $endDateUtc])
                ->whereIn('bookings.status', BookingStatus::PAYOUT_STATUSES)
                ->orderBy('booking_at')
                ->get();

            if ($bookings->isEmpty()) {
                continue;
            }

            // Split bookings for this venue
            $primeBookings = $bookings->where('is_prime', true);
            $nonPrimeBookings = $bookings->where('is_prime', false);

            // Use the user instance to call the calculateTotal method
            $instance = new self;
            $venuePrimeTotal = $instance->calculateTotal($primeBookings, $primaryManager);
            $venueNonPrimeTotal = $instance->calculateTotal($nonPrimeBookings, $primaryManager);

            // Add to venue data
            $bookingsByVenue[$venue->id] = [
                'venue' => $venue,
                'primeBookings' => $primeBookings,
                'nonPrimeBookings' => $nonPrimeBookings,
            ];

            $venuesData[] = [
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'prime_bookings_count' => $primeBookings->count(),
                'non_prime_bookings_count' => $nonPrimeBookings->count(),
                'total_guests' => $primeBookings->sum('guest_count') + $nonPrimeBookings->sum('guest_count'),
                'prime_total' => $venuePrimeTotal,
                'non_prime_total' => $venueNonPrimeTotal,
                'total_amount' => $venuePrimeTotal + $venueNonPrimeTotal,
            ];

            // Add to totals
            $primeTotalAmount += $venuePrimeTotal;
            $nonPrimeTotalAmount += $venueNonPrimeTotal;
            $allBookings = $allBookings->concat($bookings);

            // Set currency (use the first booking's currency that we find)
            if ($bookings->isNotEmpty() && $currency === 'USD') {
                $currency = $bookings->first()->currency ?? 'USD';
            }
        }

        throw_if($allBookings->isEmpty(), new RuntimeException('No bookings eligible for payout found for any venue in the specified date range.'));

        return [
            'venue' => $referenceVenue,
            'venueGroup' => $venueGroup,
            'bookings' => $allBookings,
            'bookingsByVenue' => $bookingsByVenue,
            'venuesData' => $venuesData,
            'primeTotalAmount' => $primeTotalAmount,
            'nonPrimeTotalAmount' => $nonPrimeTotalAmount,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'invoiceNumber' => $invoice->invoice_number,
            'dueDate' => $invoice->due_date,
            'currency' => $currency,
            'invoice' => $invoice,
        ];
    }
}
