<?php

namespace App\Livewire\Venue;

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
        return auth()->user()?->hasRole('super_admin') ? 'Venue Recent Bookings' : 'Your Recent Bookings';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::confirmedOrNoShow()
            ->limit(10)
            ->with('earnings', function ($query) {
                $query->where('user_id', $this->venue->user_id);
            })
            ->orderByDesc('booking_at')
            ->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at')
            ->whereHas('schedule', function ($query) {
                $query->where('venue_id', $this->venue->id);
            });

        return $table
            ->recordUrl(fn (Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->formatStateUsing(fn (Booking $booking) => "$booking->guest_name ($booking->guest_count)")
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->dateTime('D, M j g:ia'),
                TextColumn::make('concierge.user.name')
                    ->label('Booked By'),
                TextColumn::make('venue_earnings')
                    ->alignRight()
                    ->visible(fn () => auth()->user()->hasRole('venue'))
                    ->label('Earned')
                    ->formatStateUsing(function (Booking $booking) {
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
                    ->hidden(fn () => ! auth()->user()?->hasRole('venue')),
            ]);
    }
}
