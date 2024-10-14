<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use App\Models\Concierge;
use App\Services\CurrencyConversionService;
use App\Traits\ImpersonatesOther;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListConcierges extends ListRecords
{
    use ImpersonatesOther;

    protected static string $resource = ConciergeResource::class;

    protected static string $view = 'filament.pages.concierge.list-concierges';

    const bool USE_SLIDE_OVER = false;

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Concierge $record) => ViewConcierge::getUrl(['record' => $record]))
            ->query(
                Concierge::query()
                    ->with(['user.referrer'])
                    ->withCount('bookings')
                    ->join('users', 'concierges.user_id', '=', 'users.id')
                    ->orderByDesc('users.secured_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->size('xs')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('user.referrer.name')
                    ->grow(false)
                    ->size('xs')
                    ->visibleFrom('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Concierge $record) {
                        $currencyService = app(CurrencyConversionService::class);
                        $earnings = $record->user->earnings()->confirmed()->get(['amount', 'currency']);

                        $earningsArray = $earnings->groupBy('currency')
                            ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)->toArray();

                        $earningsInUSD = $currencyService->convertToUSD($earningsArray);

                        return money($earningsInUSD, 'USD');
                    }),
                TextColumn::make('bookings_count')->label('Bookings')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->numeric(),
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Concierge $record) => $this->impersonate($record->user))
                    ->hidden(fn () => isPrimaApp()),
                Action::make('viewConcierge')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading(fn (Concierge $concierge) => $concierge->user->name)
                    ->registerModalActions([
                        EditAction::make('edit')
                            ->size('sm'),
                        ViewAction::make('view')
                            ->size('sm'),
                    ])
                    ->modalContent(function (Concierge $concierge) {
                        $recentBookings = $concierge->bookings()
                            ->with('schedule.venue')
                            ->confirmed()
                            ->limit(10)
                            ->orderByDesc('confirmed_at')
                            ->get();

                        $currencyService = app(CurrencyConversionService::class);
                        $earnings = $concierge->user->earnings()->confirmed()->get(['amount', 'currency']);

                        $earningsArray = $earnings->groupBy('currency')
                            ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)->toArray();

                        $earningsInUSD = $currencyService->convertToUSD($earningsArray);

                        return view('partials.concierge-table-modal-view', [
                            'secured_at' => $concierge->user->secured_at,
                            'referrer_name' => $concierge->user->referrer?->name ?? '-',
                            'bookings_count' => number_format($concierge->bookings_count),
                            'earningsInUSD' => $earningsInUSD,
                            'recentBookings' => $recentBookings,
                        ]);
                    })
                    ->modalContentFooter(fn (Action $action) => view(
                        'partials.concierge-mobile-footer',
                        ['action' => $action]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER),
            ]);
    }
}
