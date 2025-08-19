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
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\EarningType;
use Illuminate\Support\Facades\DB;

class ListConcierges extends ListRecords
{
    use ImpersonatesOther;

    protected static string $resource = ConciergeResource::class;

    protected static string $view = 'filament.pages.concierge.list-concierges';

    const bool USE_SLIDE_OVER = false;

    public ?string $tableSortColumn = 'user.secured_at';

    public ?string $tableSortDirection = 'desc';

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Concierge $record) => ViewConcierge::getUrl(['record' => $record]))
            ->query($this->getConciergesQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->size('xs')->sortable(['last_name'])
                    ->searchable(['first_name', 'last_name', 'phone']),
                IconColumn::make('is_qr_concierge')
                    ->label('QR')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip(fn (Concierge $record): string => $record->is_qr_concierge
                        ? "QR Concierge: {$record->revenue_percentage}% revenue"
                        : 'Regular concierge')
                    ->grow(false)
                    ->alignCenter(),
                IconColumn::make('can_override_duplicate_checks')
                    ->label('Override')
                    ->boolean()
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip('Can bypass duplicate checks')
                    ->grow(false)
                    ->alignCenter()
                    ->visibleFrom('sm'),
                TextColumn::make('user.referrer.name')
                    ->sortable(['referrer_first_name'])
                    ->url(fn (Concierge $concierge) => $concierge->user->referral?->referrer_route)
                    ->grow(false)
                    ->size('xs')
                    ->default(fn (Concierge $concierge) => new HtmlString(<<<'HTML'
                        <div class='text-xs italic text-gray-600'>
                            PRIMA CREATED
                        </div>
                    HTML
                    ))
                    ->visibleFrom('sm'),
                TextColumn::make('total_earnings')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(fn ($state) => money($state, 'USD'))
                    ->sortable(),
                TextColumn::make('total_bookings')->label('Bookings')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')->alignCenter()
                    ->numeric()
                    ->sortable(),
                TextColumn::make('referrals_count')->label('Referrals')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->badge()->color('primary')
                    ->size('xs')->alignCenter()
                    ->numeric()
                    ->sortable()
                    ->action(Action::make('viewReferrals')
                        ->iconButton()
                        ->icon('heroicon-o-receipt-refund')
                        ->modalHeading(fn (Concierge $concierge) => $concierge->user->name)
                        ->modalContent(function (Concierge $concierge) {
                            $attrs = $concierge->getAttributes();
                            $directBookings = $attrs['direct_bookings'] ?? 0;
                            $referralBookings = $attrs['referral_bookings'] ?? 0;
                            $totalEarnings = $attrs['total_earnings'] ?? 0;

                            return view('partials.concierge-referrals-table-modal-view', [
                                'concierge' => $concierge,
                                'bookings_count' => number_format($directBookings),
                                'earningsInUSD' => money($totalEarnings, 'USD'),
                                'referralsBookings' => $referralBookings,
                                'referralsEarnings' => '$0.00', // We don't calculate referral earnings separately
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
                        if (!$state) {
                            return '-';
                        }

                        $date = Carbon::parse($state);
                        $timezone = auth()->user()?->timezone ?? 'UTC';

                        return $date->isCurrentYear()
                            ? $date->timezone($timezone)->format('M j, g:ia')
                            : $date->timezone($timezone)->format('M j, Y g:ia');
                    })
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $data = $this->getConciergesQuery()
                            ->with(['user'])
                            ->orderBy('users.secured_at', 'desc')
                            ->get();

                        $export = new class($data) implements FromCollection, WithHeadings
                        {
                            public function __construct(private $data) {}

                                                        public function collection()
                            {
                                return $this->data->map(function ($record) {
                                    $attrs = $record->getAttributes();

                                    return [
                                        'first_name' => $record->user?->first_name ?? '',
                                        'last_name' => $record->user?->last_name ?? '',
                                        'email' => $record->user?->email ?? '',
                                        'phone' => $record->user?->phone ?? '',
                                        'hotel_name' => $record->hotel_name ?? '',
                                        'is_qr_concierge' => $record->is_qr_concierge ? 'Yes' : 'No',
                                        'revenue_percentage' => (string)($record->revenue_percentage ?? 0),
                                        'can_override_duplicate_checks' => $record->can_override_duplicate_checks ? 'Yes' : 'No',
                                        'referrer_name' => $attrs['referrer_first_name'] ?? '',
                                        'total_earnings' => money($attrs['total_earnings'] ?? 0, 'USD'),
                                        'direct_bookings' => (string)($attrs['direct_bookings'] ?? 0),
                                        'referral_bookings' => (string)($attrs['referral_bookings'] ?? 0),
                                        'total_bookings' => (string)($attrs['total_bookings'] ?? 0),
                                        'referrals_count' => (string)($attrs['referrals_count'] ?? 0),
                                        'date_joined' => $record->user?->secured_at?->format('Y-m-d H:i:s') ?? '',
                                    ];
                                });
                            }


                            public function headings(): array
                            {
                                return [
                                    'First Name',
                                    'Last Name',
                                    'Email',
                                    'Phone',
                                    'Hotel Name',
                                    'QR Concierge',
                                    'Revenue Percentage',
                                    'Can Override Duplicate Checks',
                                    'Referrer Name',
                                    'Total Earnings',
                                    'Direct Bookings',
                                    'Referral Bookings',
                                    'Total Bookings',
                                    'Referrals Count',
                                    'Date Joined',
                                ];
                            }
                        };

                        return Excel::download($export, 'concierges-export-' . now()->format('Y-m-d-H-i-s') . '.csv');
                    }),
            ])
            ->filters([
                Filter::make('qr_concierges')
                    ->label('QR Concierges Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_qr_concierge', true))
                    ->toggle(),
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Concierge $record) => $this->impersonate($record->user))
                    ->hidden(fn () => isPrimaApp()),
                EditAction::make('edit')
                    ->iconButton(),
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
                        $attrs = $concierge->getAttributes();
                        $directBookings = $attrs['direct_bookings'] ?? 0;
                        $referralBookings = $attrs['referral_bookings'] ?? 0;
                        $totalBookings = $attrs['total_bookings'] ?? 0;
                        $totalEarnings = $attrs['total_earnings'] ?? 0;
                        $avgEarnPerBooking = $directBookings > 0 ? $totalEarnings / $directBookings : 0;

                        return view('partials.concierge-table-modal-view', [
                            'concierge' => $concierge,
                            'secured_at' => $concierge->user->secured_at,
                            'referrer_name' => $attrs['referrer_first_name'] ?? '-',
                            'referral_url' => null, // Not available in our query
                            'bookings_count' => number_format($directBookings),
                            'referralsBookings' => $referralBookings,
                            'earningsInUSD' => money($totalEarnings, 'USD'),
                            'avgEarnPerBookingInUSD' => money($avgEarnPerBooking, 'USD'),
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

            protected function getConciergesQuery(): Builder
    {
        // Build a comprehensive query that includes earnings calculations directly
        $earningTypes = [
            EarningType::CONCIERGE->value,
            EarningType::CONCIERGE_REFERRAL_1->value,
            EarningType::CONCIERGE_REFERRAL_2->value,
            EarningType::CONCIERGE_BOUNTY->value,
            EarningType::REFUND->value,
        ];

        return Concierge::query()
            ->select([
                'concierges.id',
                'concierges.user_id',
                'concierges.hotel_name',
                'concierges.is_qr_concierge',
                'concierges.revenue_percentage',
                'concierges.can_override_duplicate_checks',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                // Referrer name subquery
                DB::raw('(SELECT u2.first_name FROM users u2
                    JOIN referrals r ON u2.id = r.referrer_id
                    WHERE r.user_id = users.id LIMIT 1) as referrer_first_name'),
                // Referrals count subquery
                DB::raw('(SELECT COUNT(*) FROM concierges c2
                    JOIN users u3 ON c2.user_id = u3.id
                    WHERE u3.concierge_referral_id = concierges.id) as referrals_count'),
                // All-time earnings calculations (same logic as ConciergeOverview but all-time)
                DB::raw('COALESCE((
                    SELECT SUM(e.amount)
                    FROM earnings e
                    JOIN bookings b ON e.booking_id = b.id
                    WHERE e.user_id = users.id
                    AND b.confirmed_at IS NOT NULL
                    AND e.type IN (\'' . implode('\',\'', $earningTypes) . '\')
                    AND b.status NOT IN (\'refunded\', \'partially_refunded\')
                ), 0) as total_earnings'),
                // Direct bookings count
                DB::raw('COALESCE((
                    SELECT COUNT(DISTINCT b.id)
                    FROM earnings e
                    JOIN bookings b ON e.booking_id = b.id
                    WHERE e.user_id = users.id
                    AND b.confirmed_at IS NOT NULL
                    AND e.type IN (\'concierge\', \'concierge_bounty\')
                    AND e.type NOT IN (\'refund\')
                    AND b.status NOT IN (\'refunded\', \'partially_refunded\')
                ), 0) as direct_bookings'),
                // Referral bookings count
                DB::raw('COALESCE((
                    SELECT COUNT(DISTINCT b.id)
                    FROM earnings e
                    JOIN bookings b ON e.booking_id = b.id
                    WHERE e.user_id = users.id
                    AND b.confirmed_at IS NOT NULL
                    AND e.type IN (\'concierge_referral_1\', \'concierge_referral_2\')
                    AND b.status NOT IN (\'refunded\', \'partially_refunded\')
                ), 0) as referral_bookings'),
                // Total bookings (direct + referral)
                DB::raw('COALESCE((
                    SELECT COUNT(DISTINCT b.id)
                    FROM earnings e
                    JOIN bookings b ON e.booking_id = b.id
                    WHERE e.user_id = users.id
                    AND b.confirmed_at IS NOT NULL
                    AND e.type IN (\'' . implode('\',\'', $earningTypes) . '\')
                    AND b.status NOT IN (\'refunded\', \'partially_refunded\')
                ), 0) as total_bookings'),
            ])
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->whereNotNull('users.secured_at')
            ->with([
                'user.authentications',
                'user.referral.referrer.partner',
                'user.referral.referrer.concierge',
                'user.referrer',
            ]);
    }


    private function getReferrerName($record): string
    {
        // Try different ways to get referrer name
        if ($record->user?->referrer?->name ?? null) {
            return $record->user->referrer->name;
        }

        if ($record->user?->referral?->referrer?->name ?? null) {
            return $record->user->referral->referrer->name;
        }

        // Check attributes for referrer_first_name from the query
        if (isset($record->getAttributes()['referrer_first_name'])) {
            return $record->getAttributes()['referrer_first_name'];
        }

        return '';
    }

    private function getFormattedEarnings($record): string
    {
        // Try to get formatted earnings, fallback to manual calculation
        if (isset($record->formatted_total_earnings_in_u_s_d)) {
            return $record->formatted_total_earnings_in_u_s_d;
        }

        // Manual fallback calculation
        $totalEarnings = $record->total_earnings_in_u_s_d ?? 0;
        return '$' . number_format($totalEarnings / 100, 2);
    }

}
