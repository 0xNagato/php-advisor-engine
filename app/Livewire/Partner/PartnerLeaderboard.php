<?php

namespace App\Livewire\Partner;

use App\Models\Earning;
use App\Models\Partner;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PartnerLeaderboard extends BaseWidget
{
    public ?Partner $partner = null;

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

        $query = Earning::confirmed()->select('earnings.user_id', 'partners.id as partner_id', DB::raw('SUM(amount) as total_earned'), DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"))
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('partners', 'partners.user_id', '=', 'earnings.user_id')
            ->whereBetween('earnings.created_at', [$startDate, $endDate])
            ->groupBy('earnings.user_id', 'partners.id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(function (Earning $record) {
                $record = Partner::firstWhere(['user_id' => $record->user_id]);

                return route('filament.admin.resources.partners.view', ['record' => $record]);
            })
            ->paginated(false)
            ->columns(components: [
                TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                TextColumn::make('user_name')
                    ->label('Partner Name')
                    ->formatStateUsing(function ($state, $record) {
                        if ($this->showFilters) {
                            if (auth()->user()->partner->user_id === $record->user_id) {
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
        return 'partners.user_id';
    }
}
