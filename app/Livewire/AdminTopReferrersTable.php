<?php

namespace App\Livewire;

use App\Filament\Resources\ConciergeResource;
use App\Filament\Resources\PartnerResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

class AdminTopReferrersTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top Referrers';

    protected static bool $isLazy = true;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public function mount(): void
    {
        $this->bootedInteractsWithTable();
    }

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->select([
                'users.*',
                DB::raw('COUNT(DISTINCT referrals.id) as total_referrals'),
                DB::raw('COUNT(DISTINCT CASE WHEN referrals.secured_at IS NOT NULL THEN referrals.id END) as secured_referrals'),
                DB::raw('CASE
                    WHEN COUNT(DISTINCT referrals.id) > 0
                    THEN (COUNT(DISTINCT CASE WHEN referrals.secured_at IS NOT NULL THEN referrals.id END) * 100.0 / COUNT(DISTINCT referrals.id))
                    ELSE 0
                END as conversion_rate'),
            ])
            ->with(['roles', 'concierge', 'partner', 'venue'])
            ->leftJoin('referrals', 'referrals.referrer_id', '=', 'users.id')
            ->whereBetween('referrals.created_at', [
                $this->startDate ?? now()->subDays(30)->startOfDay(),
                $this->endDate ?? now()->endOfDay(),
            ])
            ->groupBy('users.id')
            ->having('total_referrals', '>', 0);
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'total_referrals';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->size('xs')
                    ->label('Referrer')
                    ->formatStateUsing(fn (User $record) => $record->first_name.' '.$record->last_name)
                    ->url(function (User $record): ?string {
                        if ($record->concierge) {
                            return ConciergeResource::getUrl('view', ['record' => $record->concierge]);
                        }

                        if ($record->partner) {
                            return PartnerResource::getUrl('view', ['record' => $record->partner]);
                        }

                        return null;
                    }),
                TextColumn::make('total_referrals')
                    ->size('xs')
                    ->label('Invites')
                    ->sortable(),
                TextColumn::make('secured_referrals')
                    ->size('xs')
                    ->label('Signups')
                    ->sortable(),
                TextColumn::make('conversion_rate')
                    ->size('xs')
                    ->label('Rate')
                    ->visibleFrom('sm')
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->sortable(),
            ])
            ->defaultSort($this->getDefaultTableSortColumn(), $this->getDefaultTableSortDirection());
    }
}
