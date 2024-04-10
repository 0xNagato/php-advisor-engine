<?php

namespace App\Livewire\Concierge;

use App\Models\Booking;
use App\Models\Concierge;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;

class ConciergeRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?Concierge $concierge;

    public bool $hideConcierge = false;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function getTableHeading(): string|Htmlable|null
    {
        return auth()->user()?->hasRole('super_admin') ? 'Concierge Recent Bookings' : 'Your Recent Bookings';
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmed()->where('concierge_id', $this->concierge->id);

        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = $query->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at');

        return $table
            ->recordUrl(fn(Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
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
                TextColumn::make('concierge_earnings')
                    ->alignRight()
                    ->label('Earned')
                    ->currency('USD')
                    ->hidden((bool)!auth()->user()?->hasRole('concierge') && !$this->hideConcierge),
                TextColumn::make('charity_earnings')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
