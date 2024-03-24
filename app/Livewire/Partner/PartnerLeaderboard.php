<?php

namespace App\Livewire\Partner;

use App\Models\Booking;
use App\Models\Partner;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PartnerLeaderboard extends BaseWidget
{
    public ?Partner $partner;

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

        $query = Booking::select('partners.user_id', DB::raw('SUM(partner_concierge_fee) + SUM(partner_restaurant_fee) as total_earned'), DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"))
            ->join('partners', function ($join) {
                $join->on('partners.id', '=', 'bookings.partner_concierge_id')
                    ->orOn('partners.id', '=', 'bookings.partner_restaurant_id');
            })
            ->join('users', 'users.id', '=', 'partners.user_id')
            ->whereBetween('booking_at', [$startDate, $endDate])
            ->groupBy('partners.user_id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(function (Model $record) {
                $record = Partner::where(['user_id' => $record->user_id])->first();
                return route('filament.admin.resources.partners.view', ['record' => $record]);
            })
            ->paginated(false)
            ->columns(components: [
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Partner Name')
                    ->formatStateUsing(function ($state, $record) {
                        // current user is partner display their name if not display the name of the ******* else display names
                        if ($this->showFilters) {
                            if (auth()->user()->partner->user_id === $record->user_id) {
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
        return 'partners.user_id';
    }
}
