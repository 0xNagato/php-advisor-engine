<?php

namespace App\Livewire\Admin;

use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class AdminRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmed();

        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = $query->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at');

        return $table
            ->query($query)
            ->searchable(false)
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('schedule.restaurant.restaurant_name')
                    ->label('Restaurant')
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->dateTime('D, M j'),
                TextColumn::make('platform_earnings')
                    ->alignRight()
                    ->label('Earned')
                    ->currency('USD'),
                TextColumn::make('total_fee')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('charity_earnings')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

            ]);
    }
}
