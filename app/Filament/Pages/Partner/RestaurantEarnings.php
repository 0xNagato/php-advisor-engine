<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\RestaurantReferralBookingsTable;
use App\Livewire\Partner\RestaurantReferralStats;
use App\Models\Restaurant;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class RestaurantEarnings extends Page
{
    use HasFiltersAction;

    public static ?string $title = 'Restaurant Earnings';

    protected static ?string $navigationIcon = 'heroicon-s-currency-dollar';

    protected static string $view = 'filament.pages.concierge.concierge-referral-earnings';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'partner/restaurant/earnings/{restaurantId?}';

    public ?int $restaurantId = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('partner');
    }

    public function getHeading(): string|Htmlable
    {
        if ($this->restaurantId) {
            $restaurant = Restaurant::query()->find($this->restaurantId);

            return "{$restaurant->restaurant_name} Bookings";
        }

        return 'My Restaurant Earnings';
    }

    public function mount(?int $restaurantId = null): void
    {
        $this->restaurantId = $restaurantId;

        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        $restaurant = new Restaurant();
        if ($this->restaurantId) {
            $restaurant = Restaurant::query()->find($this->restaurantId);
        }

        return [
            RestaurantReferralStats::make([
                'restaurant' => $restaurant,
                'columnSpan' => 'full',
            ]),
            RestaurantReferralBookingsTable::make([
                'restaurant' => $restaurant,
                'columnSpan' => 'full',
            ]),
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Date Range')
                ->iconButton()
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
        ];
    }
}
