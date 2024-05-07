<?php

namespace App\Livewire\Concierge;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Concierge;
use App\Models\Earning;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
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
            ->whereIn('earnings.type', ['concierge_referral_1', 'concierge_referral_2'])
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->whereNotNull('earnings.confirmed_at') // Explicitly specify the table name here
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->orderBy('bookings.created_at', 'desc')
            ->with('booking.concierge.user');

        if ($this->concierge->exists) {
            $bookingsQuery->whereHas('booking', function ($query) {
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
                    ->color(function (string $state) {
                        return match ($state) {
                            'concierge_referral_1' => 'gold',
                            'concierge_referral_2' => 'silver',
                            default => null,
                        };
                    })
                    ->icon(function (string $state) {
                        return match ($state) {
                            'concierge_referral_1' => 'tabler-square-rounded-number-1-filled',
                            'concierge_referral_2' => 'tabler-square-rounded-number-2-filled',
                            default => null,
                        };
                    }),
                TextColumn::make('booking.concierge.user.name')
                    ->label('Concierge'),
                TextColumn::make('booking.created_at')
                    ->label('Date')
                    ->dateTime('M j'),
                TextColumn::make('amount')
                    ->label('Earnings')
                    ->alignRight()
                    ->currency(fn (Earning $record) => $record->booking->currency),
            ]);
    }
}
