<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Models\Region;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class InvoiceSmall extends Widget
{
    protected static string $view = 'livewire.invoice-small';

    protected static bool $isLazy = false;

    public Booking $booking;

    public Region $region;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
        $this->region = Region::query()->find($booking->city);
    }

    #[Computed]
    public function dayDisplay(): string
    {
        $timezone = 'UTC';

        if (auth()->check()) {
            $timezone = auth()->user()->timezone;
        } elseif (session()->has('timezone')) {
            $timezone = session('timezone');
        }

        $bookingDate = $this->booking->booking_at->startOfDay();
        $today = today();
        $tomorrow = now($timezone)->addDay()->startOfDay();

        if ($bookingDate->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Today';
        }

        if ($bookingDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
            return 'Tomorrow';
        }

        return $this->booking->booking_at->format('D, M j');
    }
}
