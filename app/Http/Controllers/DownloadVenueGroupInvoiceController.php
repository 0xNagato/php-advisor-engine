<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateVenueGroupInvoice;
use App\Models\VenueGroup;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DownloadVenueGroupInvoiceController extends Controller
{
    public function __invoke(Request $request, VenueGroup $venueGroup, string $startDate, string $endDate)
    {
        // Get the user's timezone
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

        // Parse dates in user's timezone to get the correct date components
        $startDateCarbon = Carbon::parse($startDate, $userTimezone);
        $endDateCarbon = Carbon::parse($endDate, $userTimezone);

        // Check if regeneration is requested
        $shouldRegenerate = $request->boolean('regenerate', false);

        // Find existing invoice or generate a new one
        $invoice = VenueInvoice::query()
            ->where('venue_group_id', $venueGroup->id)
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->first();

        // Regenerate if requested or if invoice doesn't exist
        if ($shouldRegenerate || ! $invoice) {
            // If regenerating and invoice exists, delete the old one
            if ($invoice) {
                // Delete the old PDF file if it exists
                if ($invoice->pdf_path && Storage::disk('do')->exists($invoice->pdf_path)) {
                    Storage::disk('do')->delete($invoice->pdf_path);
                }

                // Delete the old invoice record
                $invoice->delete();
            }

            // Generate new invoice
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
