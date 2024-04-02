<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'livewire.customer-invoice';

    public bool $download = false;

    public Booking $booking;

    public bool $showConcierges = false;

    public function mount(string|int $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if($this->record->status !== BookingStatus::CONFIRMED, 404);

        if (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('partner') || auth()->user()->hasRole('concierge')) {
            $this->showConcierges = true;
        }

        $this->authorizeAccess();

        $this->booking = $this->record;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Booking Information')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime('D, M j g:i a')
                            ->timezone(auth()->user()->timezone)
                            ->inlineLabel(),
                        TextEntry::make('booking_at')
                            ->label('Reservation Time:')
                            ->dateTime('D, M j g:i a')
                            ->timezone(auth()->user()->timezone)
                            ->inlineLabel(),
                        TextEntry::make('concierge.user.name')
                            ->label('Booked By:')
                            ->inlineLabel(),
                        TextEntry::make('restaurant.restaurant_name')
                            ->label('Restaurant:')
                            ->inlineLabel(),
                    ]),

                Section::make('Guest Information')
                    ->schema([
                        TextEntry::make('guest_name')->hiddenLabel(),
                        TextEntry::make('guest_phone')
                            ->formatStateUsing(fn($state) => formatPhoneNumber($state))
                            ->hiddenLabel(),
                        TextEntry::make('guest_count')
                            ->label('Guest Count:')
                            ->inlineLabel(),
                        TextEntry::make('total_fee')
                            ->label('Reservation Fee:')
                            ->money('USD', divideBy: 100)
                            ->inlineLabel(),
                    ]),
            ]);
    }
}
