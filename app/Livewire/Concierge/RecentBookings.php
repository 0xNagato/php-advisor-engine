<?php

namespace App\Livewire\Concierge;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Concierge;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class RecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    public ?Concierge $concierge = null;

    protected static ?string $heading = 'Recent Bookings';

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::with('schedule.venue')
                    ->confirmed()
                    ->where('concierge_id', $this->concierge->id)
                    ->orderByDesc('confirmed_at')
            )
            ->recordUrl(fn (Booking $booking): string => ViewBooking::getUrl(['record' => $booking]))
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('schedule.venue.name')
                    ->searchable()
                    ->label('Venue')
                    ->formatStateUsing(fn (Booking $booking): string => view('components.two-line-cell', [
                        'primary' => $booking->schedule->venue->name,
                        'secondary' => $booking->booking_at->format('M d, Y g:i A'),
                    ])->render())
                    ->html()
                    ->size('sm'),
                TextColumn::make('total_fee')
                    ->label('Fee')
                    ->size('xs')
                    ->formatStateUsing(function (Booking $booking) {
                        if (! $booking->is_prime) {
                            return new HtmlString('<span class="text-xs italic text-gray-500">Non-Prime</span>');
                        }

                        return $booking->total_fee > 0
                            ? money($booking->total_fee, $booking->currency)
                            : '-';
                    })
                    ->alignment(Alignment::End),
            ])
            ->paginated([5, 10, 25, 50, 100]);
    }
}
