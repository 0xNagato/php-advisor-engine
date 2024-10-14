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

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?Concierge $concierge = null;

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
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::confirmed()
            ->limit(10)
            ->orderByDesc('booking_at')
            ->with('earnings', function ($query) {
                $query->where('user_id', $this->concierge->user_id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('concierge_id', $this->concierge->id);

        return $table
            ->recordUrl(fn (Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->paginated(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->columns([
                TextColumn::make('schedule.venue.name')
                    ->label('Venue')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->size('xs')
                    ->dateTime('D, M j'),
                TextColumn::make('earnings.amount')
                    ->alignRight()
                    ->size('xs')
                    ->label('Earned')
                    ->formatStateUsing(function (Booking $booking) {
                        $total = $booking->earnings->sum('amount');

                        return money($total, $booking->currency);
                    }),
            ]);
    }
}
