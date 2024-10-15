<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Filament\Resources\PartnerResource;
use App\Filament\Resources\VenueResource\Pages\ViewVenue;
use App\Models\Partner;
use App\Models\Referral;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListPartners extends ListRecords
{
    use ImpersonatesOther;

    protected static string $resource = PartnerResource::class;

    protected static string $view = 'filament.pages.partner.partner-list';

    const bool USE_SLIDE_OVER = false;

    public function getHeading(): Htmlable|string
    {
        if (auth()->user()->hasRole('super_admin')) {
            return 'Partners';
        }

        return 'My Earnings';
    }

    public function table(Table $table): Table
    {
        $query = $this->getPartnersQuery();

        return $table
            ->recordUrl(fn (Partner $record) => ViewPartner::getUrl(['record' => $record]))
            ->query($query)
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('user.name')
                    ->size('xs')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('total_earned')
                    ->label('Earned')
                    ->size('xs')
                    ->grow(false)
                    ->money('USD', divideBy: 100),
                TextColumn::make('bookings')->label('Bookings')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->numeric(),
                TextColumn::make('user.authentications.login_at')
                    ->label('Last Login')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Partner $record) {
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
                    ->action(fn (Partner $record) => $this->impersonate($record->user))
                    ->hidden(fn () => isPrimaApp()),
                Action::make('viewConcierge')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading(fn (Partner $partner) => $partner->user->name)
                    ->registerModalActions([
                        EditAction::make('edit')
                            ->size('sm'),
                        ViewAction::make('view')
                            ->size('sm'),
                    ])
                    ->modalContent(function (Partner $partner) {
                        $referrals = $partner->referrals()->latest()->take(10)->with([
                            'user.concierge', 'user.venue',
                        ])->get()
                            ->map(function (Referral $referral) {
                                $referral->viewRoute = match ($referral->type) {
                                    'concierge' => ViewConcierge::getUrl(['record' => $referral->user->concierge]),
                                    'venue' => ViewVenue::getUrl(['record' => $referral->user->venue]),
                                    default => null
                                };

                                return $referral;
                            });

                        return view('partials.partner-table-modal-view', [
                            'secured_at' => $partner->user->secured_at,
                            'percentage' => $partner->percentage,
                            'bookings_count' => number_format($partner->bookings),
                            'total_earned' => $partner->total_earned,
                            'referrals' => $referrals,
                            'last_login' => $partner->user->authentications()->latest('login_at')->first()->login_at ?? null,
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

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->getKey();
    }

    protected function getPartnersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Partner::query()
            ->select('partners.id', 'partners.percentage', 'users.id as user_id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                DB::raw('COALESCE(SUM(amount), 0) as total_earned'),
                DB::raw('COALESCE(COUNT(case when earnings.type in ("partner_concierge", "partner_venue") then 1 else null end), 0) as bookings'))
            ->join('users', 'users.id', '=', 'partners.user_id')
            ->leftJoin('earnings', function (Builder $join) {
                $join->on('earnings.user_id', '=', 'users.id');
            })
            ->groupBy('partners.id', 'users.id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);
    }
}
