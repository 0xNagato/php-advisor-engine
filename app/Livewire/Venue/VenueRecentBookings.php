<?php

namespace App\Livewire\Venue;

use App\Enums\BookingStatus;
use App\Filament\Actions\Venue\MarkAsNoShowAction;
use App\Filament\Actions\Venue\ReverseMarkAsNoShowAction;
use App\Models\Booking;
use App\Models\Venue;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class VenueRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?Venue $venue = null;

    public bool $hideVenue = false;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function getTableHeading(): string|Htmlable|null
    {
        return auth()->user()?->hasActiveRole('super_admin') ? 'Venue Recent Bookings' : 'Your Recent Bookings';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::query()
            ->recentBookings()
            ->limit(10)
            ->with('earnings', function ($query) {
                $query->where('user_id', $this->venue->user_id);
            })
            ->orderByDesc('booking_at')
            ->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at')
            ->forVenue($this->venue->id);

        return $table
            ->recordUrl(fn (Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    // ->formatStateUsing(fn (Booking $booking) => "$booking->guest_name ($booking->guest_count)")
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
                            HTML);
                        }

                        $total = $booking->earnings->sum('amount');

                        return money($total, $booking->currency);
                    }),
            ])
            ->paginated(false)
            ->actions([
                ActionGroup::make([
                    MarkAsNoShowAction::make('mark no show'),
                    ReverseMarkAsNoShowAction::make('reverse no show'),
                ])
                    ->tooltip('Actions')
                    ->hidden(fn () => ! auth()->user()?->hasActiveRole('venue')),
            ]);
    }
}
