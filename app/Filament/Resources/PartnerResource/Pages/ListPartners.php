<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Models\Partner;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListPartners extends ListRecords
{
    use ImpersonatesOther;

    protected static string $resource = PartnerResource::class;

    protected static string $view = 'filament.pages.partner.partner-list';

    public ?string $tableSortColumn = 'user.secured_at';

    public ?string $tableSortDirection = 'desc';

    const bool USE_SLIDE_OVER = false;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create Partner'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Partner $record) => ViewPartner::getUrl(['record' => $record]))
            ->query($this->getPartnersQuery())
            ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('user.name')
                    ->label('Name')
                    ->size('xs')
                    ->searchable(['users.first_name', 'users.last_name']),

                TextColumn::make('total_earned')
                    ->label('Earned')
                    ->size('xs')
                    ->grow(false)
                    ->money('USD', divideBy: 100),

                TextColumn::make('bookings_count')
                    ->label('Bookings')
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
                    ->action(fn (Partner $record) => $this->impersonate($record->user))
                    ->hidden(fn () => isPrimaApp()),

                Action::make('viewPartner')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading(fn (Partner $partner) => $partner->user->name)
                    ->registerModalActions([
                        EditAction::make('edit')
                            ->size('sm'),
                        ViewAction::make('view')
                            ->size('sm'),
                    ])
                    ->modalContent(fn (Partner $partner) => view('partials.partner-table-modal-view', [
                        'partner' => $partner,
                        'secured_at' => $partner->user->secured_at,
                        'percentage' => $partner->percentage,
                        'bookings_count' => number_format($partner->bookings()->count()),
                        'total_earned' => $partner->getTotalEarnings(),
                        'last_login' => optional($partner->user->authentications()->latest('login_at')->first())->login_at,
                    ]))
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

    protected function getPartnersQuery(): Builder
    {
        return Partner::query()
            ->with(['user', 'user.authentications' => function ($query) {
                $query->latest('login_at')->limit(1);
            }])
            ->select('partners.*')
            ->addSelect([
                'bookings_count' => function ($query) {
                    $query->selectRaw('COUNT(DISTINCT bookings.id)')
                        ->from('bookings')
                        ->whereNotNull('confirmed_at')
                        ->where(function ($q) {
                            $q->whereColumn('bookings.partner_concierge_id', 'partners.id')
                                ->orWhereColumn('bookings.partner_venue_id', 'partners.id');
                        });
                },
                'total_earned' => function ($query) {
                    $query->selectRaw('COALESCE(SUM(
                        CASE
                            WHEN bookings.partner_concierge_id = partners.id AND bookings.partner_venue_id = partners.id THEN earnings.amount
                            ELSE earnings.amount / 2
                        END
                    ), 0)')
                        ->from('earnings')
                        ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
                        ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
                        ->whereNotNull('bookings.confirmed_at')
                        ->where(function ($q) {
                            $q->whereColumn('bookings.partner_concierge_id', 'partners.id')
                                ->orWhereColumn('bookings.partner_venue_id', 'partners.id');
                        });
                },
            ])
            ->orderByDesc('total_earned');
    }
}
