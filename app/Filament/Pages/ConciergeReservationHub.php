<?php

namespace App\Filament\Pages;

use App\Livewire\BookingWidget;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ConciergeReservationHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.concierge-reservation-hub';

    protected static ?string $title = 'Reservation Hub';

    protected static ?int $navigationSort = -4;

    protected static ?string $slug = 'concierge/reservation-hub';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    public function getHeading(): string|Htmlable
    {
        return 'Welcome, ' . auth()->user()->name;
    }

    public function getHeaderWidgets(): array
    {
        return [
            BookingWidget::make(),
        ];
    }
}
