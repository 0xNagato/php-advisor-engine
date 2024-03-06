<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    public ?string $type = null;

    public ?int $id = null;

    public ?bool $hideConcierge = false;

    protected string|int|array $columnSpan = 'full';

    // public function mount(): void
    // {
    //     if (auth()->user()?->hasRole('concierge')) {
    //         $this->columnSpan = 1;
    //     }
    // }

    public function table(Table $table): Table
    {
        $query = Booking::query();

        if ($this->type === 'concierge' && $this->id) {
            $query = Booking::where('concierge_id', $this->id);
        } elseif ($this->type === 'restaurant' && $this->id) {
            $query = Booking::whereHas('schedule', function ($query) {
                $query->where('restaurant_id', $this->id);
            });
        } else {
            if (auth()->user()?->hasRole('concierge')) {
                $query = Booking::where('concierge_id', auth()->user()->concierge->id);
            }

            if (auth()->user()?->hasRole('restaurant')) {
                $query = Booking::whereHas('schedule', function ($query) {
                    $query->where('restaurant_id', auth()->user()->restaurant->id);
                });
            }
        }

        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = $query->whereBetween('created_at', [$startDate, $endDate])->orderByDesc('created_at');

        ds(!auth()->user()?->hasRole('restaurant'));

        return $table
            ->query($query)
            ->searchable(false)
            ->columns([
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->toggleable(isToggledHiddenByDefault: (bool)auth()->user()?->hasRole('restaurant'))
                    ->searchable(),
                TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->numeric()
                    ->hidden((bool)auth()->user()?->hasRole('concierge') || !auth()->user()->hasRole('restaurant') || $this->hideConcierge),
                TextColumn::make('schedule.restaurant.restaurant_name')
                    ->label('Restaurant')
                    ->hidden((bool)auth()->user()?->hasRole('restaurant'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('booking_at')
                    ->label('When')
                    ->dateTime('D, M j'),

                TextColumn::make('guest_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_count')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_fee')
                    ->alignRight()
                    ->currency('USD')
                    ->hidden((bool)!auth()->user()?->hasRole('super_admin'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('concierge_fee')
                    ->alignRight()
                    ->label('Earnings')
                    ->currency('USD')
                    ->hidden((bool)!auth()->user()?->hasRole('concierge') && !$this->hideConcierge),
                TextColumn::make('restaurant_fee')
                    ->alignRight()
                    ->label('Earnings')
                    ->currency('USD')
                    ->hidden((bool)!auth()->user()?->hasRole('restaurant')),
                TextColumn::make('platform_fee')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden((bool)!auth()->user()?->hasRole('super_admin')),
                TextColumn::make('charity_fee')
                    ->alignRight()
                    ->currency('USD')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
