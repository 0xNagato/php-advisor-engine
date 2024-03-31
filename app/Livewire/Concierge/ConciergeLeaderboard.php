<?php

namespace App\Livewire\Concierge;

use App\Models\Booking;
use App\Models\Concierge;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConciergeLeaderboard extends BaseWidget
{
    protected static bool $isLazy = false;

    public ?Concierge $concierge;

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

        $query = Booking::select('concierges.user_id', 'bookings.concierge_id', DB::raw('sum(concierge_earnings) as total_earned'), DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"))
            ->join('concierges', 'concierges.id', '=', 'bookings.concierge_id')
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->groupBy('concierges.user_id', 'bookings.concierge_id') // Add 'bookings.concierge_id' to the groupBy clause
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(function (Model $record) {
                $record = Concierge::find($record->concierge_id);

                if (!$record) {
                    return null;
                }

                return route('filament.admin.resources.concierges.view', ['record' => $record]);
            })
            ->paginated(false)
            ->columns(components: [
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('user_name')
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
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Earned')
                    ->currency('USD'),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'concierges.user_id';
    }
}
