<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookings extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
            )
            ->columns([
                // ...
            ]);
    }
}
