<?php

namespace App\Livewire;

use App\Models\Booking;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class InvoiceSmall extends Widget
{
    protected static string $view = 'livewire.invoice-small';

    public Booking $booking;

    #[Computed]
    public function dayDisplay(): string
    {
        $bookingDate = $this->booking->booking_at->startOfDay();
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        if ($bookingDate->equalTo($today)) {
            return 'Today';
        }

        if ($bookingDate->equalTo($tomorrow)) {
            return 'Tomorrow';
        }

        return $this->booking->booking_at->format('l');
    }
}
