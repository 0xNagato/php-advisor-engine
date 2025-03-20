<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateVenueGroupInvoice;
use App\Models\VenueGroup;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DownloadVenueGroupInvoiceController extends Controller
{
    public function __invoke(VenueGroup $venueGroup, string $startDate, string $endDate)
    {
        // Get the user's timezone
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

        // Parse dates in user's timezone to get the correct date components
        $startDateCarbon = Carbon::parse($startDate, $userTimezone);
        $endDateCarbon = Carbon::parse($endDate, $userTimezone);

        $invoice = VenueInvoice::query()
            ->where('venue_group_id', $venueGroup->id)
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->first();

        if (! $invoice) {
            $invoice = GenerateVenueGroupInvoice::run($venueGroup, $startDate, $endDate);
        }

        // Check if we're in HTML preview mode (for development)
        if (config('app.invoice_html_preview')) {
            // Use the static method from the action to prepare the view data
            $data = GenerateVenueGroupInvoice::prepareViewData($venueGroup, $startDateCarbon, $endDateCarbon, $invoice);

            // Return the HTML view directly
            return view('pdfs.venue-group-invoice', $data);
        }

        throw_unless(Storage::disk('do')->exists($invoice->pdf_path), new RuntimeException('Invoice PDF not found'));

        return Storage::disk('do')->download(
            $invoice->pdf_path,
            $invoice->name().'.pdf'
        );
    }
}
