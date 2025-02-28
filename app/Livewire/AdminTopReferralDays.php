<?php

namespace App\Livewire;

use App\Models\Referral;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AdminTopReferralDays extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Referral Activity by Day';

    protected static bool $isLazy = true;

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->date;
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'invites';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    public function table(Table $table): Table
    {
        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $startDate = isset($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();
        $endDate = isset($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        // Convert to UTC for database query
        $startDateUTC = $startDate->copy()->setTimezone('UTC')->startOfDay();
        $endDateUTC = $endDate->copy()->setTimezone('UTC')->endOfDay();

        // Build the query
        $query = Referral::query()
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as invites'),
                DB::raw('SUM(CASE WHEN secured_at IS NOT NULL THEN 1 ELSE 0 END) as signups'),
                DB::raw('ROUND((SUM(CASE WHEN secured_at IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as rate'),
            ])
            ->whereBetween('created_at', [$startDateUTC, $endDateUTC])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('invites');

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('date')
                    ->size('xs')
                    ->label('Date')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('M j, Y'))
                    ->sortable(),
                TextColumn::make('invites')
                    ->size('xs')
                    ->label('Invites')
                    ->sortable(),
                TextColumn::make('signups')
                    ->size('xs')
                    ->label('Signups')
                    ->sortable(),
                TextColumn::make('rate')
                    ->size('xs')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->sortable(),
            ])
            ->defaultSort('invites', 'desc');
    }
}
