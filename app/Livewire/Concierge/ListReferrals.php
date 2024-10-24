<?php

namespace App\Livewire\Concierge;

use App\Enums\EarningType;
use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Concierge;
use App\Models\Earning;
use App\Services\CurrencyConversionService;
use Exception;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Database\Query\Builder;

class ListReferrals extends BaseWidget
{
    use InteractsWithPageFilters;

    public ?Concierge $concierge = null;

    protected static ?string $heading = 'Referrals';

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getReferralsQuery())
            ->recordUrl(fn ($record) => ViewConcierge::getUrl(['record' => $record]))
            ->openRecordUrlInNewTab()
            ->columns([
                IconColumn::make('user.referral.referrer_id')
                    ->label('Level')
                    ->color(fn (int $state) => match ($state) {
                        $this->concierge->user_id => 'gold',
                        default => 'silver',
                    })
                    ->icon(fn (int $state) => match ($state) {
                        $this->concierge->user_id => 'tabler-square-rounded-number-1-filled',
                        default => 'tabler-square-rounded-number-2-filled',
                    }),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn (Concierge $concierge): string => view('components.two-line-cell', [
                        'primary' => $concierge->user->name,
                        'secondary' => ucfirst($concierge->user->referral->type),
                    ])->render())
                    ->html()
                    ->size('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Concierge $concierge) {
                        $type = $this->concierge->user_id === $concierge->user->referral->referrer_id ?
                            EarningType::CONCIERGE_REFERRAL_1 : EarningType::CONCIERGE_REFERRAL_2;
                        $earnings = Earning::confirmed()
                            ->whereHas('booking', function ($query) use ($concierge) {
                                $query->where('concierge_id', $concierge->id);
                            })
                            ->where('type', $type)
                            ->get(['amount', 'currency'])
                            ->groupBy('currency')
                            ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)
                            ->toArray();
                        $currencyService = app(CurrencyConversionService::class);
                        $earned = $currencyService->convertToUSD($earnings);

                        return money($earned, 'USD');
                    }),
                TextColumn::make('bookings_count')->label('Bookings')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')->alignCenter()
                    ->numeric()->sortable(),
                TextColumn::make('user.referral.secured_at')
                    ->label('Date Joined')
                    ->size('xs')
                    ->date()
                    ->timezone(auth()->user()->timezone)
                    ->alignment(Alignment::End),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'concierge' => 'Concierge',
                        'venue' => 'Venue',
                    ])
                    ->label('Referral Type'),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public function getReferralsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $this->concierge->load('concierges.concierges');
        $ids = $this->concierge->concierges->pluck('id');
        $ids2 = $this->concierge->concierges->flatMap(fn ($concierge) => $concierge->concierges->pluck('id'));
        $conciergeIds = $ids->merge($ids2)->unique();

        return Concierge::query()
            ->with(['user.venue', 'user.referral'])
            ->withCount([
                'bookings' => fn ($query) => $query->confirmed(),
            ])
            ->whereHas('user', function (Builder $query) {
                $query->whereNotNull('concierge_referral_id');
            })
            ->whereIn('id', $conciergeIds);
    }
}
