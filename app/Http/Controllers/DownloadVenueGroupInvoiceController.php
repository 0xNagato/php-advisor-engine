<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateVenueGroupInvoice;
use App\Enums\VenueInvoiceStatus;
use App\Models\VenueGroup;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DownloadVenueGroupInvoiceController extends Controller
{
    const int PREVIEW_RANDOM_STRING_LENGTH = 8;

    public function __invoke(Request $request, VenueGroup $venueGroup, string $startDate, string $endDate)
    {
        // Get the user's timezone
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

        // Parse dates in the user's timezone to get the correct date components
        $startDateCarbon = Carbon::parse($startDate, $userTimezone);
        $endDateCarbon = Carbon::parse($endDate, $userTimezone);

        if ($request->boolean('preview')) {
            $referenceVenue = $venueGroup->venues()->first();

            if (!$referenceVenue) {
                abort(400, 'Cannot generate invoice preview: Venue group has no venues.');
            }

            $tempInvoice = new VenueInvoice([
                'venue_id' => $referenceVenue->id,
                'venue_group_id' => $venueGroup->id,
                'start_date' => $startDateCarbon->format('Y-m-d'),
                'end_date' => $endDateCarbon->format('Y-m-d'),
                'due_date' => now()->addDays(15),
                'currency' => 'USD',
                'invoice_number' => 'preview-' . str()->random(self::PREVIEW_RANDOM_STRING_LENGTH),
                'status' => VenueInvoiceStatus::DRAFT,
            ]);

            $data = GenerateVenueGroupInvoice::prepareViewData(
                $venueGroup,
                $startDateCarbon,
                $endDateCarbon,
                $tempInvoice
            );

            return view('pdfs.venue-group-invoice', $data);
        }

        // Check if regeneration is requested
        $shouldRegenerate = $request->boolean('regenerate');

        // Find an existing invoice or generate a new one
        $invoice = VenueInvoice::query()
            ->where('venue_group_id', $venueGroup->id)
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->first();

        // Regenerate if requested or if the invoice doesn't exist
        if ($shouldRegenerate || !$invoice) {
            // If regenerating and invoice exist, delete the old one
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

        throw_unless(Storage::disk('do')->exists($invoice->pdf_path), new RuntimeException('Invoice PDF not found'));

        return Storage::disk('do')->download(
            $invoice->pdf_path,
            $invoice->name() . '.pdf'
        );
    }
}
