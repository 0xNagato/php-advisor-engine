<?php

namespace App\Livewire\Concierge;

use App\Enums\EarningType;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Concierge;
use App\Models\Earning;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
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
            ->whereIn('earnings.type', [EarningType::CONCIERGE_REFERRAL_1, EarningType::CONCIERGE_REFERRAL_2])
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->whereNotNull('bookings.confirmed_at') // Explicitly specify the table name here
            ->whereNotIn('bookings.status', ['cancelled', 'refunded'])
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
            ->columns([
                IconColumn::make('type')
                    ->label('Level')
                    ->color(fn (string $state) => match ($state) {
                        EarningType::CONCIERGE_REFERRAL_1->value => 'gold',
                        EarningType::CONCIERGE_REFERRAL_2->value => 'silver',
                        default => null,
                    })
                    ->icon(fn (string $state) => match ($state) {
                        EarningType::CONCIERGE_REFERRAL_1->value => 'tabler-square-rounded-number-1-filled',
                        EarningType::CONCIERGE_REFERRAL_2->value => 'tabler-square-rounded-number-2-filled',
                        default => null,
                    }),
                TextColumn::make('booking.concierge.user.name')
                    ->label('Concierge'),
                TextColumn::make('booking.created_at')
                    ->label('Date')
                    ->dateTime('M j'),
                TextColumn::make('amount')
                    ->label('Earnings')
                    ->alignRight()
                    ->money(fn (Earning $record) => $record->booking->currency, divideBy: 100),
            ]);
    }
}
