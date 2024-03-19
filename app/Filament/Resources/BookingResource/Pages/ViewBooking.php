<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Booking Information')
                    ->schema([
                        TextEntry::make('concierge.user.name')
                            ->url(fn($record) => route('filament.resources.user-record', $record->concierge))
                            ->label('Booked By:')
                            ->inlineLabel(),
                        TextEntry::make('restaurant.restaurant_name')
                            ->label('Restaurant:')
                            ->inlineLabel(),
                    ]),

                Section::make('Guest Information')
                    ->schema([
                        TextEntry::make('guest_name')->hiddenLabel(),
                        TextEntry::make('guest_email')->hiddenLabel(),
                        TextEntry::make('guest_phone')->hiddenLabel(),
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

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
