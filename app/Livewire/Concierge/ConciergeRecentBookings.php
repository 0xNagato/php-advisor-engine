<?php

namespace App\Livewire\Concierge;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

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
        return auth()->user()?->hasActiveRole('super_admin') ? 'Concierge Recent Bookings' : 'Your Recent Bookings';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::query()
            ->limit(10)
            ->orderByDesc('booking_at')
            ->with('earnings', function ($query) {
                $query->where('user_id', $this->concierge->user_id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('concierge_id', $this->concierge->id)
            ->whereIn('status', [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
                BookingStatus::CANCELLED,
                BookingStatus::NO_SHOW,
                BookingStatus::REFUNDED,
                BookingStatus::PARTIALLY_REFUNDED,
            ]);

        return $table
            ->recordUrl(fn (Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->paginated(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->columns([
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->size('xs')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->size('xs')
                    ->dateTime('D, M j'),
                TextColumn::make('id')
                    ->alignRight()
                    ->size('xs')
                    ->label('Earned')
                    ->formatStateUsing(function (Booking $booking) {
                        $labelStatus = [BookingStatus::CANCELLED, BookingStatus::NO_SHOW, BookingStatus::REFUNDED];
                        if (in_array($booking->status, $labelStatus)) {
                            return new HtmlString(<<<HTML
                                <span class="text-xs italic text-gray-500">
                                    {$booking->status->label()}
                                </span>
                            HTML);
                        }

                        $total = $booking->earnings->sum('amount') ?? 0;
                        if ($booking->status === BookingStatus::PARTIALLY_REFUNDED) {
                            $total = $booking->earnings->calculateRemaining() ?? 0;
                        }

                        return $total > 0
                            ? money($total, $booking->currency)
                            : '-';
                    }),
            ]);
    }
}
