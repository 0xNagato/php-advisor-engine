<?php

namespace App\Livewire;

use App\Models\Concierge;
use Filament\Widgets\Widget;

class ConciergeStats extends Widget
{
    protected static string $view = 'livewire.concierge-stats';

    public ?Concierge $concierge;
}
