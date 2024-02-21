<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class BookingWidget extends Widget
{
    protected static string $view = 'filament.widgets.booking-widget';

    public array $restaurants;

    public function mount(): void
    {

    }
}
