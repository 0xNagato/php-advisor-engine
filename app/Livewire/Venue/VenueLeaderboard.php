<?php

namespace App\Livewire\Venue;

use App\Models\Earning;
use App\Models\Venue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VenueLeaderboard extends BaseWidget
{
    protected static bool $isLazy = true;

    public ?Venue $venue = null;

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

        $query = Earning::confirmed()->select(['earnings.user_id', 'venues.id as venue_id', DB::raw('SUM(amount) as total_earned'), 'venues.name'])
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('venues', 'venues.user_id', '=', 'earnings.user_id')
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->groupBy('earnings.user_id', 'venues.id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(fn (Model $record) => route('filament.admin.resources.venues.view', ['record' => $record->venue_id]))
            ->paginated(false)
            ->deferLoading()
            ->columns(components: [
                TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Venue Name')
                    ->formatStateUsing(function ($state, $record) {
                        // current user is venue display their name if not display the name of the ******* else display names
                        if ($this->showFilters) {
                            if (auth()->user()->venue->user_id === $record->user_id) {
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
        return 'venues.user_id';
    }
}
