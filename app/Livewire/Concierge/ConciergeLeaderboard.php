<?php

namespace App\Livewire\Concierge;

use App\Models\Concierge;
use App\Models\Earning;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Use ConciergeOverallLeaderboard instead
 */
class ConciergeLeaderboard extends BaseWidget
{
    public ?Concierge $concierge = null;

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

        $query = Earning::confirmed()->select('earnings.user_id', 'concierges.id as concierge_id', DB::raw('SUM(amount) as total_earned'), DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"))
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('concierges', 'concierges.user_id', '=', 'earnings.user_id')
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->groupBy('earnings.user_id', 'concierges.id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(function (Model $record) {

                $record = Concierge::query()->find($record->concierge_id);

                if (! $record || ! auth()->user()->hasRole('super_admin')) {
                    return null;
                }

                return route('filament.admin.resources.concierges.view', ['record' => $record]);
            })
            ->deferLoading()
            ->paginated(false)
            ->columns(components: [
                TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                TextColumn::make('user_name')
                    ->label('Concierge Name')
                    ->formatStateUsing(function ($state, $record) {
                        // current user is concierge display their name if not display the name of the ******* else display names
                        if ($this->showFilters) {
                            if (auth()->user()->concierge->user_id === $record->user_id) {
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
        return 'concierges.user_id';
    }
}
