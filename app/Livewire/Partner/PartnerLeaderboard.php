<?php

/** @noinspection UnknownInspectionInspection */
/** @noinspection CallableParameterUseCaseInTypeContextInspection */

namespace App\Livewire\Partner;

use App\Models\Earning;
use App\Models\Partner;
use Carbon\Carbon;
use Exception;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

/**
 * @deprecated Use PartnerOverallLeaderboard instead
 */
class PartnerLeaderboard extends BaseWidget
{
    public ?Partner $partner = null;

    public int|string|array $columnSpan;

    public bool $showFilters = false;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    protected function getTableQuery(): Builder
    {
        return Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('users', 'users.id', '=', 'earnings.user_id')
            ->join('partners', 'partners.user_id', '=', 'earnings.user_id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereBetween('bookings.booking_at', [$this->startDate, $this->endDate])
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
            ->groupBy('earnings.user_id', 'partners.id', 'earnings.currency')
            ->select(
                'earnings.user_id',
                'partners.id as partner_id',
                DB::raw('SUM(earnings.amount) as total_earned'),
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                'earnings.currency'
            )
            ->orderByDesc('total_earned')
            ->limit(10);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->getStateUsing(fn ($record, $rowLoop) => $rowLoop->iteration),
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
                    ->formatStateUsing(fn ($state, $record) => $record->currency.' '.number_format($state / 100, 2)),
                TextColumn::make('currency')
                    ->label('Currency'),
            ])
            ->filters([
                SelectFilter::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ])
                    ->attribute('earnings.currency')
                    ->default('USD'),
            ])
            ->paginated(false);
    }

    public function getTableRecordKey(Model $record): string
    {
        return 'partners.user_id';
    }

    public function getTableHeading(): ?string
    {
        $startDate = Carbon::parse($this->startDate)->format('M j');
        $endDate = Carbon::parse($this->endDate)->format('M j');

        return "Partner Leaderboards ($startDate - $endDate)";
    }
}
