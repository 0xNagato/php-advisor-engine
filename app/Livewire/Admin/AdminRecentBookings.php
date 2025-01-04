<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class AdminRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public int|string|array $columnSpan;

    protected static ?string $heading = 'Recent Bookings';

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::query()
            ->select([
                'bookings.id',
                'bookings.booking_at',
                'bookings.total_fee',
                'bookings.total_refunded',
                'bookings.currency',
                'bookings.is_prime',
                'bookings.platform_earnings',
                'venues.name as venue_name',
                'bookings.status',
            ])
            ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
            ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereNotNull('bookings.guest_phone')
            ->whereIn('bookings.status',
                [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED, BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED, BookingStatus::CANCELLED])
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->orderByDesc('bookings.created_at')
            ->limit(10);

        return $table
            ->recordUrl(fn (Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->paginated(false)
            ->deferLoading()
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateHeading('Earnings will show here when bookings begin!')
            ->columns([
                TextColumn::make('venue_name')
                    ->label('Venue')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->size('xs')
                    ->dateTime('D, M j'),
                TextColumn::make('total_fee')
                    ->label('Fee')
                    ->size('xs')
                    ->formatStateUsing(function (Booking $booking) {
                        if ($booking->status === BookingStatus::CANCELLED) {
                            return new HtmlString('<span class="text-xs italic text-gray-500">Cancelled</span>');
                        }

                        if (! $booking->is_prime) {
                            return new HtmlString(
                                '<span class="text-xs italic text-gray-500">Non-Prime</span><br>'.
                                '<span class="text-xs text-green-600">'.money($booking->platform_earnings ?? 0, $booking->currency).' (Platform Earnings)</span>'
                            );
                        }

                        if ($booking->status === BookingStatus::REFUNDED) {
                            return new HtmlString(
                                '<span class="text-xs italic text-gray-500">Refunded</span><br>'.
                                '<span class="text-xs text-red-600">-'.money($booking->total_refunded ?? 0, $booking->currency).'</span>'
                            );
                        }

                        if ($booking->status === BookingStatus::PARTIALLY_REFUNDED) {
                            $remaining = ($booking->total_fee ?? 0) - ($booking->total_refunded ?? 0);

                            return new HtmlString(
                                '<span class="text-xs italic text-gray-500">Partially Refunded</span><br>'.
                                '<span class="text-xs text-red-600">-'.money($booking->total_refunded ?? 0, $booking->currency).'</span><br>'.
                                '<span class="text-xs text-amber-600">'.money($remaining, $booking->currency).' remaining</span>'
                            );
                        }

                        return $booking->total_fee > 0
                            ? money($booking->total_fee - ($booking->total_refunded ?? 0), $booking->currency)
                            : '-';
                    }),
            ]);
    }
}
