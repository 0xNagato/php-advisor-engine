<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Concierge;
use App\Models\Earning;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Support\Htmlable;

class ConciergeReferralBookingsTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    public Concierge $concierge;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function getTableHeading(): string|Htmlable|null
    {
        return '';
    }

    public function table(Table $table): Table
    {
        $userId = auth()->id();

        $startDate = Carbon::parse($this->filters['startDate'] ?? now()->subDays(30));
        $endDate = Carbon::parse($this->filters['endDate'] ?? now());

        $bookingsQuery = Earning::query()
            ->where('earnings.user_id', $userId)
            ->whereIn('earnings.type', ['partner_concierge'])
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->whereNotNull('bookings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->orderBy('bookings.created_at', 'desc')
            ->with('booking.concierge.user');

        if ($this->concierge->exists) {
            $bookingsQuery->whereHas('booking', function (Builder $query) {
                $query->where('concierge_id', $this->concierge->id);
            });
        }

        return $table
            ->paginationPageOptions([10, 25, 50])
            ->query($bookingsQuery)
            ->recordUrl(fn (Earning $record) => ViewBooking::getUrl([$record->booking]))
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('booking.concierge.user.name')
                    ->label('Concierge'),
                TextColumn::make('booking.created_at')
                    ->label('Date')
                    ->dateTime('D, M j'),
                TextColumn::make('amount')
                    ->label('Earnings')
                    ->alignRight()
                    ->money(fn (Earning $record) => $record->booking->currency, divideBy: 100),
            ]);
    }
}
