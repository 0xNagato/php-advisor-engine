<?php

namespace App\Livewire\Venue;

use App\Models\Earning;
use App\Models\Venue;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class VenueLeaderboard extends BaseWidget
{
    protected static bool $isLazy = true;

    public ?Venue $venue = null;

    public bool $showFilters = false;

    public int|string|array $columnSpan;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->startDate ?? now()->subDays(30);
        $endDate = $this->endDate ?? now();

        $query = Earning::query()
            ->select([
                'earnings.user_id',
                'venues.id as venue_id',
                DB::raw('SUM(CASE WHEN earnings.type = "venue" THEN earnings.amount ELSE 0 END) as total_earned'),
                'venues.name',
                'venues.region',
                'earnings.currency',
                DB::raw('COUNT(DISTINCT bookings.id) as booking_count'),
            ])
            ->whereNotNull('earnings.confirmed_at')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('venues', 'venues.user_id', '=', 'earnings.user_id')
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->groupBy('earnings.user_id', 'venues.id', 'venues.region', 'earnings.currency')
            ->orderByDesc('total_earned')
            ->limit(10);

        return $table
            ->query($query)
            ->recordUrl(fn (Model $record) => route('filament.admin.resources.venues.view', ['record' => $record->venue_id]))
            ->paginated(false)
            ->deferLoading()
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Venue Name')
                    ->formatStateUsing(function ($state, $record) {
                        if ($this->showFilters) {
                            if (auth()->user()->venue?->user_id === $record->user_id) {
                                return 'Your Venue';
                            }

                            return $record->name[0].str_repeat('*', strlen($record->name) - 1);
                        }

                        return $state;
                    }),
                TextColumn::make('booking_count')
                    ->label('Bookings')
                    ->alignRight(),
                TextColumn::make('total_earned')
                    ->label('Earned')
                    ->money(fn ($record) => $record->currency, divideBy: 100),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'venues.user_id';
    }
}
