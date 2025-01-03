<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Filament\Resources\VenueResource;
use App\Models\Region;
use App\Models\Venue;
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

    public ?string $tableSortColumn = 'user.secured_at';

    public ?string $tableSortDirection = 'desc';

    const bool USE_SLIDE_OVER = false;

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Venue $record) => ViewVenue::getUrl(['record' => $record]))
            ->query(
                Venue::query()
                    ->with(['partnerReferral.user.partner', 'user.authentications'])
                    ->withCount([
                        'bookings' => function ($query) {
                            $query->confirmed();
                        },
                    ])
                    ->join('users', 'venues.user_id', '=', 'users.id')
                    ->orderByDesc('users.updated_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('partnerReferral.user.name')->label('Partner')
                    ->url(fn (Venue $record) => $record->partnerReferral?->user?->partner
                        ? ViewPartner::getUrl(['record' => $record->partnerReferral->user->partner])
                        : null)
                    ->grow(false)
                    ->size('xs')
                    ->visibleFrom('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(fn (Venue $venue) => $venue->formatted_total_earnings_in_u_s_d),
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
                        $lastLogin = $record->user?->authentications()?->orderByDesc('login_at')->first();
                        if ($lastLogin && $lastLogin->login_at) {
                            return Carbon::parse($lastLogin->login_at, auth()->user()->timezone)->diffForHumans();
                        }

                        return 'Never';
                    })
                    ->default('Never'),
                TextColumn::make('user.secured_at')
                    ->label('Date Joined')
                    ->size('xs')
                    ->formatStateUsing(function ($state) {
                        $date = Carbon::parse($state);

                        return $date->isCurrentYear()
                            ? $date->timezone(auth()->user()->timezone)->format('M j, g:ia')
                            : $date->timezone(auth()->user()->timezone)->format('M j, Y g:ia');
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                        $lastLogging = $venue->user->authentications()->latest('login_at')->first()->login_at ?? null;

                        return view('partials.venue-table-modal-view', [
                            'user' => $venue->user,
                            'secured_at' => $venue->user->secured_at,
                            'referrer_name' => $venue->user->referrer?->name ?? '-',
                            'bookings_count' => number_format($venue->bookings_count),
                            'earningsInUSD' => $venue->formattedTotalEarningsInUSD,
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
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('region')
                    ->options(Region::query()->pluck('name', 'id')),
            ]);
    }
}
