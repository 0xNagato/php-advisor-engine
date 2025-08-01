<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use App\Models\Concierge;
use App\Models\User;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListConcierges extends ListRecords
{
    use ImpersonatesOther;

    protected static string $resource = ConciergeResource::class;

    protected static string $view = 'filament.pages.concierge.list-concierges';

    const bool USE_SLIDE_OVER = false;

    public ?string $tableSortColumn = 'bookings_count';

    public ?string $tableSortDirection = 'desc';

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn(Concierge $record) => ViewConcierge::getUrl(['record' => $record]))
            ->query($this->getConciergesQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->size('xs')->sortable(['last_name'])
                    ->searchable(['first_name', 'last_name', 'phone']),
                IconColumn::make('is_qr_concierge')
                    ->label('QR')
                    ->icon(fn(bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip(fn(Concierge $record): string => $record->is_qr_concierge
                        ? "QR Concierge: {$record->revenue_percentage}% revenue"
                        : 'Regular concierge')
                    ->grow(false)
                    ->alignCenter(),
                IconColumn::make('can_override_duplicate_checks')
                    ->label('Override')
                    ->boolean()
                    ->icon(fn(bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip('Can bypass duplicate checks')
                    ->grow(false)
                    ->alignCenter()
                    ->visibleFrom('sm'),
                TextColumn::make('user.referrer.name')
                    ->sortable(['referrer_first_name'])
                    ->url(fn(Concierge $concierge) => $concierge->user->referral?->referrer_route)
                    ->grow(false)
                    ->size('xs')
                    ->default(fn(Concierge $concierge) => new HtmlString(<<<'HTML'
                        <div class='text-xs italic text-gray-600'>
                            PRIMA CREATED
                        </div>
                    HTML
                    ))
                    ->visibleFrom('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(fn(Concierge $concierge) => $concierge->formatted_total_earnings_in_u_s_d),
                TextColumn::make('bookings_count')->label('Bookings')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')->alignCenter()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('concierges_count')->label('Referrals')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->badge()->color('primary')
                    ->size('xs')->alignCenter()
                    ->numeric()
                    ->sortable()
                    ->action(Action::make('viewReferrals')
                        ->iconButton()
                        ->icon('heroicon-o-receipt-refund')
                        ->modalHeading(fn(Concierge $concierge) => $concierge->user->name)
                        ->modalContent(function (Concierge $concierge) {
                            $referralsBookings = $concierge->concierges->map(fn($concierge
                            ) => $concierge->bookings()->confirmed()->count())->sum();

                            return view('partials.concierge-referrals-table-modal-view', [
                                'concierge' => $concierge,
                                'bookings_count' => number_format($concierge->bookings_count),
                                'earningsInUSD' => $concierge->formatted_total_earnings_in_u_s_d,
                                'referralsBookings' => $referralsBookings,
                                'referralsEarnings' => $concierge->formatted_referral_earnings_in_u_s_d,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->slideOver(self::USE_SLIDE_OVER)),
                TextColumn::make('user.authentications.login_at')
                    ->label('Last Login')
                    ->sortable()
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Concierge $record) {
                        $lastLogin = $record->user->authentications()->orderByDesc('login_at')->first();
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
            ->filters([
                Filter::make('qr_concierges')
                    ->label('QR Concierges Only')
                    ->query(fn(Builder $query): Builder => $query->where('is_qr_concierge', true))
                    ->toggle(),
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn(Concierge $record) => $this->impersonate($record->user))
                    ->hidden(fn() => isPrimaApp()),
                EditAction::make('edit')
                    ->iconButton(),
                Action::make('viewConcierge')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading(fn(Concierge $concierge) => $concierge->user->name)
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

                        $lastLogin = $concierge->user->authentications()->latest('login_at')->first()->login_at ?? null;
                        $avgEarnPerBooking = $concierge->bookings_count > 0
                            ? $concierge->total_earnings_in_u_s_d / $concierge->bookings_count
                            : 0;
                        $referralsBookings = $concierge->concierges->map(fn($concierge
                        ) => $concierge->bookings()->confirmed()->count())->sum();

                        return view('partials.concierge-table-modal-view', [
                            'concierge' => $concierge,
                            'secured_at' => $concierge->user->secured_at,
                            'referrer_name' => $concierge->user->referrer?->name ?? '-',
                            'referral_url' => $concierge->user->referral?->referrer_route ?? null,
                            'bookings_count' => number_format($concierge->bookings_count),
                            'referralsBookings' => $referralsBookings,
                            'earningsInUSD' => $concierge->formatted_total_earnings_in_u_s_d,
                            'avgEarnPerBookingInUSD' => money($avgEarnPerBooking, 'USD'),
                            'recentBookings' => $recentBookings,
                            'last_login' => $lastLogin,
                        ]);
                    })
                    ->modalContentFooter(fn(Action $action) => view(
                        'partials.modal-actions-footer',
                        ['action' => $action]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER),
            ]);
    }

    protected function getConciergesQuery(): Builder
    {
        return Concierge::query()
            ->addSelect([
                'referrer_first_name' => User::query()->select('users.first_name')
                    ->join('referrals', 'users.id', '=', 'referrals.referrer_id')
                    ->whereColumn('referrals.user_id', 'concierges.user_id')
                    ->limit(1),
            ])
            ->with([
                'user.authentications',
                'user.referral.referrer.partner',
                'user.referral.referrer.concierge',
            ])
            ->withCount([
                'bookings' => function ($query) {
                    $query->confirmed();
                }, 'referrals', 'concierges',
            ]);
    }
}
