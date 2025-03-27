<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Sentry;

use function Spatie\LaravelPdf\Support\pdf;

class GenerateCustomerInvoice implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(BookingPaid $event): void
    {
        info('Generating invoice for booking', ['booking_id' => $event->booking->id]);

        $path = config('app.env').'/invoices/prima-invoice-'.$event->booking->id.'.pdf';

        try {
            pdf()
                ->view('livewire.customer-invoice-download', [
                    'booking' => $event->booking,
                    'download' => true,
                ])
                ->disk('do', 'public')
                ->save($path);

            $invoiceUrl = Storage::disk('do')->url($path);

            $event->booking->update([
                'invoice_path' => $path,
            ]);

            info('Invoice generated successfully', [
                'booking_id' => $event->booking->id,
                'invoice_path' => $path,
                'invoice_url' => $invoiceUrl,
            ]);
        } catch (Exception $e) {
            logger()->error('Failed to generate invoice', ['booking_id' => $event->booking->id, 'exception' => $e->getMessage()]);
            Sentry::captureException($e);
        }
    }
}
