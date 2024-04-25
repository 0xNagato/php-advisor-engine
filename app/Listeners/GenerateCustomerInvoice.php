<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
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

        $path = config('app.env') . '/invoices/prima-invoice-' . $event->booking->id . '.pdf';

        pdf()
            ->view('livewire.customer-invoice-download', [
                'booking' => $event->booking,
                'download' => true,
            ])
            ->disk('do', 'public')
            ->save($path);

        $event->booking->update([
            'invoice_path' => $path,
        ]);

        info('Invoice generated', ['booking_id' => $event->booking->id, 'invoice_path' => $path]);
    }
}
