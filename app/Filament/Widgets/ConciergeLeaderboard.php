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
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Concierge Name'),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->currency('USD')
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'concierges.user_id';
    }
}
