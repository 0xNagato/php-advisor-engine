<?php

namespace App\Livewire\Partner;

use App\Models\Booking;
use App\Models\Partner;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;

class PartnerRecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?Partner $partner;

    public bool $hidePartner = false;

    public int|string|array $columnSpan;

    public function getTableHeading(): string|Htmlable|null
    {
        return auth()->user()?->hasRole('super_admin') ? 'Partner Recent Bookings' : 'Your Recent Bookings';
    }

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $partnerWithBookings = Partner::withAllBookings()->find($this->partner->id);

        $query = Booking::confirmed()->where(function ($query) use ($partnerWithBookings) {
            $query->whereIn('id', $partnerWithBookings->conciergeBookings->pluck('id')->concat($partnerWithBookings->restaurantBookings->pluck('id')));
        })->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at');

        return $table
            ->recordUrl(fn(Booking $booking) => route('filament.admin.resources.bookings.view', $booking))
            ->query($query)
            ->searchable(false)
            ->columns([
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('schedule.restaurant.restaurant_name')
                    ->label('Restaurant')
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('Date')
                    ->dateTime('D, M j'),
                TextColumn::make('partner_earnings')
                    ->alignRight()
                    ->label('Earned')
                    ->currency('USD'),
                TextColumn::make('charity_earnings')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
