<?php

/** @noinspection PhpDynamicFieldDeclarationInspection */

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property Booking $record
 */
class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'livewire.customer-invoice';

    public bool $download = false;

    public Booking $booking;

    public bool $showConcierges = false;

    public Region $region;

    public function mount(string|int $record): void
    {
        $this->record = Booking::with('earnings.user')
            ->firstWhere('id', $record);

        abort_if(! in_array($this->record->status, [BookingStatus::CONFIRMED, BookingStatus::NO_SHOW], true), 404);

        if (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('partner') || auth()->user()->hasRole('concierge')) {
            $this->showConcierges = true;
        }

        $this->authorizeAccess();

        $this->booking = $this->record;
        $this->region = Region::query()->find($this->booking->city);
    }
}
