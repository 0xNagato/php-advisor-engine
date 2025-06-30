<?php

namespace App\Livewire\VenueManager;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Venue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class VenueGroupRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    /** @var Collection<int, Venue> */
    public Collection $venues;

    public int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return 'Recent Bookings';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::query()
            ->recentBookings()
            ->with(['earnings', 'venue.user'])
            ->orderByDesc('booking_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('schedule', function ($query) {
                $query->whereIn('venue_id', $this->venues->pluck('id'));
            });

        return $table
            ->recordUrl(fn (Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Bookings will show here when they begin!')
            ->columns([
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->size('xs'),
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Reservation')
                    ->size('xs')
                    ->dateTime('M j g:ia'),
                TextColumn::make('concierge.user.name')
                    ->size('xs')
                    ->label('Booked By'),
                TextColumn::make('total_fee')
                    ->label('Total Fee')
                    ->size('xs')
                    ->visible(fn () => auth()->user()?->hasActiveRole('super_admin'))
                    ->formatStateUsing(function (Booking $booking) {
                        if (! $booking->is_prime) {
                            return new HtmlString('<span class="text-xs italic text-gray-500">Non-Prime</span>');
                        }

                        return money($booking->total_fee, $booking->currency);
                    }),
                TextColumn::make('venue_earnings')
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
                            HTML
                            );
                        }
                        $total = $booking->earnings
                            ->whereIn('type', ['venue', 'venue_paid'])
                            ->sum('amount');

                        return money($total, $booking->currency);
                    }),
            ])
            ->paginated(true)
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100]);
    }
}
