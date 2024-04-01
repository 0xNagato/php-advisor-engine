<?php

namespace App\Filament\Pages\Concierge;

use App\Livewire\Booking\BookingWidget;
use Filament\Pages\Page;

class ConciergeReservationHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.concierge-reservation-hub';

    protected static ?string $title = 'Reservation Hub';

    protected static ?int $navigationSort = -4;

    protected static ?string $slug = 'concierge/reservation-hub';

    protected ?string $heading = 'Reservation Request';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    public function getHeaderWidgets(): array
    {
        return [
            BookingWidget::make(),
        ];
    }
}
