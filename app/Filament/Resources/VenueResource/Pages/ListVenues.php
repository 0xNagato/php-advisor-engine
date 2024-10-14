<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\Resources\VenueResource;
use App\Models\Region;
use App\Models\Venue;
use App\Services\CurrencyConversionService;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListVenues extends ListRecords
{
    use ImpersonatesOther;

    protected static string $resource = VenueResource::class;

    protected static string $view = 'filament.pages.venue.list-venues';

    const bool USE_SLIDE_OVER = false;

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Venue $record) => ViewVenue::getUrl(['record' => $record]))
            ->query(
                Venue::query()
                    ->with(['partnerReferral.user', 'user.authentications'])
                    ->withCount('bookings')
                    ->join('users', 'venues.user_id', '=', 'users.id')
                    ->orderByDesc('users.secured_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('partnerReferral.user.name')->label('Partner')
                    ->grow(false)
                    ->size('xs')
                    ->visibleFrom('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Venue $record) {
                        $currencyService = app(CurrencyConversionService::class);
                        $earnings = $record->earnings()
                            ->where('type', 'venue')
                            ->confirmed()->get(['amount', 'currency']);

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
                TextColumn::make('user.authentications.login_at')
                    ->label('Last Login')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Venue $record) {
                        $lastLogin = $record->user->authentications()->orderByDesc('login_at')->first();
                        if ($lastLogin && $lastLogin->login_at) {
                            return Carbon::parse($lastLogin->login_at, auth()->user()->timezone)->diffForHumans();
                        }

                        return 'Never';
                    })
                    ->default('Never'),
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Venue $record) => $this->impersonate($record->user))
                    ->hidden(fn () => isPrimaApp()),
                Action::make('viewConcierge')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading(fn (Venue $concierge) => $concierge->user->name)
                    ->registerModalActions([
                        EditAction::make('edit')
                            ->size('sm'),
                        ViewAction::make('view')
                            ->size('sm'),
                    ])
                    ->modalContent(function (Venue $venue) {
                        $recentBookings = $venue->bookings()
                            ->with('concierge.user')
                            ->confirmed()
                            ->limit(10)
                            ->orderByDesc('created_at')
                            ->get();

                        $currencyService = app(CurrencyConversionService::class);
                        $earnings = $venue->earnings()->confirmed()->get(['amount', 'currency']);

                        $earningsArray = $earnings->groupBy('currency')
                            ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)->toArray();

                        $earningsInUSD = $currencyService->convertToUSD($earningsArray);

                        $lastLogging = $venue->user->authentications()->latest('login_at')->first()->login_at ?? null;

                        return view('partials.venue-table-modal-view', [
                            'secured_at' => $venue->user->secured_at,
                            'referrer_name' => $venue->user->referrer?->name ?? '-',
                            'bookings_count' => number_format($venue->bookings_count),
                            'earningsInUSD' => $earningsInUSD,
                            'recentBookings' => $recentBookings,
                            'last_login' => $lastLogging,
                            'contacts' => $venue->contacts,
                        ]);
                    })
                    ->modalContentFooter(fn (Action $action) => view(
                        'partials.modal-actions-footer',
                        ['action' => $action]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER),
            ])
            ->paginated([5, 10, 25])
            ->filters([
                SelectFilter::make('region')
                    ->options(Region::query()->pluck('name', 'id')),
            ]);
    }
}
