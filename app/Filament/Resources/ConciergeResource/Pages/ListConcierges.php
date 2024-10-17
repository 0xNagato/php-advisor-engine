<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\ConciergeResource;
use App\Models\Concierge;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
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
                    ->with(['user.referrer.referral', 'user.authentications', 'user.referral.referrer.partner', 'user.referral.referrer.concierge'])
                    ->withCount(['bookings' => function ($query) {
                        $query->where('status', BookingStatus::CONFIRMED);
                    }])
                    ->join('users', 'concierges.user_id', '=', 'users.id')
                    ->orderByDesc('users.secured_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->size('xs')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('user.referrer.name')
                    ->url(fn (Concierge $concierge) => $concierge->user->referral?->referrer_route)
                    ->grow(false)
                    ->size('xs')
                    ->visibleFrom('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(fn (Concierge $concierge) => $concierge->formatted_total_earnings_in_u_s_d),
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
                    ->formatStateUsing(function (Concierge $record) {
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

                        $lastLogin = $concierge->user->authentications()->latest('login_at')->first()->login_at ?? null;

                        return view('partials.concierge-table-modal-view', [
                            'secured_at' => $concierge->user->secured_at,
                            'referrer_name' => $concierge->user->referrer?->name ?? '-',
                            'referral_url' => $concierge->user->referral?->referrer_route ?? null,
                            'bookings_count' => number_format($concierge->bookings_count),
                            'earningsInUSD' => $concierge->formatted_total_earnings_in_u_s_d,
                            'recentBookings' => $recentBookings,
                            'last_login' => $lastLogin,
                        ]);
                    })
                    ->modalContentFooter(fn (Action $action) => view(
                        'partials.modal-actions-footer',
                        ['action' => $action]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER),
            ]);
    }
}
