<?php

namespace App\Livewire\Restaurant;

use App\Models\Booking;
use App\Models\Restaurant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class RestaurantRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?Restaurant $restaurant;

    public bool $hideRestaurant = false;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmed()->whereHas('schedule', function ($query) {
            $query->where('restaurant_id', $this->restaurant->id);
        });

        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = $query->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at');

        return $table
            ->query($query)
            ->searchable(false)
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->dateTime('D, M j'),
                TextColumn::make('restaurant_earnings')
                    ->alignRight()
                    ->label('Earned')
                    ->currency('USD'),
                TextColumn::make('charity_earnings')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
