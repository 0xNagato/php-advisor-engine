<?php

namespace App\Livewire;

use App\Models\Concierge;
use Filament\Widgets\Widget;

class RestaurantStats extends Widget
{
    protected static string $view = 'livewire.restaurant-stats';
    public ?Concierge $concierge;
    protected string|int|array $columnSpan = 'full';
}
