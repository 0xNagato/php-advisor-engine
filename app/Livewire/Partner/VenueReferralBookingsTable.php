<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Earning;
use App\Models\Venue;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;

class VenueReferralBookingsTable extends BaseWidget
{
    use InteractsWithPageFilters;

    public Venue $venue;

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
            ->whereIn('earnings.type', ['partner_venue'])
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->whereNotNull('bookings.confirmed_at') // Explicitly specify the table name here
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->orderBy('bookings.created_at', 'desc')
            ->with('booking.concierge.user');

        if ($this->venue->exists) {
            $bookingsQuery->whereHas('booking.schedule', function ($query) {
                $query->where('venue_id', $this->venue->id);
            });
        }

        return $table
            ->paginationPageOptions([10, 25, 50])
            ->query($bookingsQuery)
            ->recordUrl(fn (Earning $record) => ViewBooking::getUrl(['record' => $record->booking]))
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->columns([
                TextColumn::make('booking.venue.name')
                    ->label('Venue'),
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
