<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConciergeLeaderboard extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return false;

        // return auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('concierge');
        return auth()->user()->hasRole('super_admin');
    }

    public function mount(): void
    {
        if (auth()->user()?->hasRole('concierge')) {
            $this->columnSpan = 'full';
        }
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Booking::select('concierges.user_id', DB::raw('sum(total_fee * payout_concierge / 100) as total_earned'), DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"))
            ->join('concierges', 'concierges.id', '=', 'bookings.concierge_id')
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->groupBy('concierges.user_id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Concierge Name')
                    ->formatStateUsing(function ($state, $record) {
                        // current user is concierge display their name if not display the name of the ******* else display names
                        if (auth()->user()->hasRole('concierge')) {
                            if (auth()->user()->concierge->user_id === $record->user_id) {
                                return 'You';
                            }

                            return '*********';
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->currency('USD'),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'concierges.user_id';
    }
}
