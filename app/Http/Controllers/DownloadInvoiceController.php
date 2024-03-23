<?php

namespace App\Http\Controllers;

use App\Models\Booking;

use function Spatie\LaravelPdf\Support\pdf;

class DownloadInvoiceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)->firstOrFail();

        return pdf()
            ->view('livewire.customer-invoice-download', [
                'booking' => $booking,
                'download' => true,
            ])
            ->download("prima-invoice-{$booking->id}.pdf");
    }
}
