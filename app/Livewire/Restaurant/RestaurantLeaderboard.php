<?php

namespace App\Livewire\Restaurant;

use App\Models\Earning;
use App\Models\Restaurant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RestaurantLeaderboard extends BaseWidget
{
    protected static bool $isLazy = true;

    public ?Restaurant $restaurant = null;

    public bool $showFilters = false;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Earning::confirmed()->select('earnings.user_id', 'restaurants.id as restaurant_id', DB::raw('SUM(amount) as total_earned'), 'restaurants.restaurant_name')
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('restaurants', 'restaurants.user_id', '=', 'earnings.user_id')
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->groupBy('earnings.user_id', 'restaurants.id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(function (Model $record) {
                $restaurant = Restaurant::where('user_id', $record->user_id)->first();

                return route('filament.admin.resources.restaurants.view', ['record' => $restaurant]);
            })
            ->paginated(false)
            ->columns(components: [
                TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                TextColumn::make('restaurant_name')
                    ->label('Restaurant Name')
                    ->formatStateUsing(function ($state, $record) {
                        // current user is restaurant display their name if not display the name of the ******* else display names
                        if ($this->showFilters) {
                            if (auth()->user()->restaurant->user_id === $record->user_id) {
                                return 'You';
                            }

                            return '*********';
                        }

                        return $state;
                    }),
                TextColumn::make('total_earned')
                    ->label('Earned')
                    ->money('USD', divideBy: 100),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'restaurants.user_id';
    }
}
