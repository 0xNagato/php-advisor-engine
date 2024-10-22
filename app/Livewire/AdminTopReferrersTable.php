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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class AdminTopReferrersTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top Referrers';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public function getTableData(): Collection
    {
        $tempStartDate = $this->startDate ?? now()->subDays(30)->startOfDay();
        $tempEndDate = $this->endDate ?? now()->endOfDay();

        $cacheKey = "admin_top_referrers_{$tempStartDate->toDateTimeString()}_{$tempEndDate->toDateTimeString()}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tempStartDate, $tempEndDate) {
            return User::query()
                ->with(['roles', 'concierge', 'partner', 'venue']) // Add eager loading here
                ->select([
                    'users.*',  // Select all user columns
                    DB::raw('COUNT(DISTINCT referrals.id) as total_referrals'),
                    DB::raw('COUNT(DISTINCT CASE WHEN referrals.secured_at IS NOT NULL THEN referrals.id END) as secured_referrals'),
                    DB::raw('COALESCE(SUM(earnings.amount), 0) as total_earnings'),
                ])
                ->leftJoin('referrals', 'referrals.referrer_id', '=', 'users.id')
                ->leftJoin('earnings', function ($join) {
                    $join->on('earnings.user_id', '=', 'users.id')
                        ->where('earnings.type', 'LIKE', '%referral%');
                })
                ->whereBetween('referrals.created_at', [$tempStartDate, $tempEndDate])
                ->groupBy('users.id')
                ->having('total_referrals', '>', 0)
                ->orderByDesc('total_referrals')
                ->limit(25)
                ->get()
                ->map(function ($user) {
                    $user->conversion_rate = $user->total_referrals > 0
                        ? ($user->secured_referrals / $user->total_referrals) * 100
                        : 0;

                    return $user;
                });
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()
                ->select([
                    'users.*',  // Select all user columns instead of just specific ones
                    DB::raw('COUNT(DISTINCT referrals.id) as total_referrals'),
                    DB::raw('COUNT(DISTINCT CASE WHEN referrals.secured_at IS NOT NULL THEN referrals.id END) as secured_referrals'),
                    DB::raw('CASE
                        WHEN COUNT(DISTINCT referrals.id) > 0
                        THEN (COUNT(DISTINCT CASE WHEN referrals.secured_at IS NOT NULL THEN referrals.id END) * 100.0 / COUNT(DISTINCT referrals.id))
                        ELSE 0
                    END as conversion_rate'),
                ])
                ->with(['roles', 'concierge', 'partner', 'venue']) // Eager load all needed relationships
                ->leftJoin('referrals', 'referrals.referrer_id', '=', 'users.id')
                ->whereBetween('referrals.created_at', [
                    $this->startDate ?? now()->subDays(30)->startOfDay(),
                    $this->endDate ?? now()->endOfDay(),
                ])
                ->groupBy('users.id')  // Group by all selected columns
                ->having('total_referrals', '>', 0)
            )
            ->columns([
                TextColumn::make('name')
                    ->size('xs')
                    ->label('Referrer')
                    ->formatStateUsing(fn (User $record) => $record->first_name.' '.$record->last_name)
                    ->url(function (User $record): string {
                        if ($record->concierge) {
                            return ConciergeResource::getUrl('view', ['record' => $record->concierge]);
                        }

                        return PartnerResource::getUrl('view', ['record' => $record->partner]);
                    }),
                TextColumn::make('total_referrals')
                    ->size('xs')
                    ->label('Invites')
                    ->sortable(),
                TextColumn::make('secured_referrals')
                    ->size('xs')
                    ->label('Secured')
                    ->sortable(),
                TextColumn::make('conversion_rate')
                    ->size('xs')
                    ->label('Rate')
                    ->visibleFrom('sm')
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->sortable(),
            ])
            ->defaultSort('total_referrals', 'desc');
    }
}
