<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Models\Region;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class InvoiceSmall extends Widget
{
    protected static string $view = 'livewire.invoice-small';

    protected static ?string $pollingInterval = null;

    public Booking $booking;

    public Region $region;

    public bool $showAmount = true;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
        $this->region = Region::query()->find($booking->city)
            ?? Region::query()->find($booking->venue->region)
            ?? Region::default();
    }

    #[Computed]
    public function dayDisplay(): string
    {
        $timezone = 'UTC';

        if (auth()->check()) {
            $timezone = auth()->user()->timezone;
        } elseif (session()?->has('timezone')) {
            $timezone = session('timezone');
        }

        $bookingDate = $this->booking->booking_at->setTimezone($timezone)->startOfDay();
        $today = today();
        $tomorrow = $today->copy()->addDay();

        if ($bookingDate->equalTo($today)) {
            return 'Today';
        }

        if ($bookingDate->equalTo($tomorrow)) {
            return 'Tomorrow';
        }

        return $bookingDate->format('D, M j');
    }
}
