<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Enums\EarningType;
use App\Filament\Resources\ConciergeResource;
use App\Models\Concierge;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class ListConcierges extends ListRecords
{
    use ImpersonatesOther;

    public ?array $data = [];

    protected static string $resource = ConciergeResource::class;

    protected static string $view = 'filament.pages.concierge.list-concierges';

    const bool USE_SLIDE_OVER = false;

    public ?string $tableSortColumn = 'user.secured_at';

    public ?string $tableSortDirection = 'desc';

    public function mount(): void
    {
        parent::mount();

        if (blank($this->data)) {
            $this->data = [
                'search' => '',
                'date_filter' => 'all_time',
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Concierge $record) => ViewConcierge::getUrl(['record' => $record]))
            ->query($this->getConciergesQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->size('xs')->sortable(['last_name']),
                IconColumn::make('is_qr_concierge')
                    ->label('QR')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip(fn (Concierge $record): string => $record->is_qr_concierge
                        ? "QR Concierge: {$record->revenue_percentage}% revenue"
                        : 'Regular concierge')
                    ->grow(false)
                    ->alignCenter()
                    ->visibleFrom('sm'),
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
                TextColumn::make('last_login')
                    ->label('Last Login')
                    ->sortable()
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->default('Never')
                    ->formatStateUsing(function ($state): string {
                        if (blank($state) || $state === 'Never') {
                            return 'Never';
                        }

                        return Carbon::parse($state, auth()->user()->timezone)->diffForHumans();
                    }),
                TextColumn::make('user.secured_at')
                    ->label('Date Joined')
                    ->visibleFrom('sm')
                    ->size('xs')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
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
                        // Get the current table query which includes all filters
                        $data = $this->getFilteredTableQuery()
                            ->with(['user'])
                            ->orderBy('users.secured_at', 'desc')
                            ->get();

                        // Get filter data for filename and headers
                        $dateFilter = $this->data['date_filter'] ?? 'all_time';
                        $startDate = $this->data['start_date'] ?? null;
                        $endDate = $this->data['end_date'] ?? null;

                        $export = new class($data, $this) implements FromCollection, WithHeadings
                        {
                            public function __construct(private $data, private $parent) {}

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
                                        'revenue_percentage' => (string) ($record->revenue_percentage ?? 0),
                                        'can_override_duplicate_checks' => $record->can_override_duplicate_checks ? 'Yes' : 'No',
                                        'referrer_name' => $attrs['referrer_first_name'] ?? '',
                                        'total_earnings' => money($attrs['total_earnings'] ?? 0, 'USD'),
                                        'direct_bookings' => (string) ($attrs['direct_bookings'] ?? 0),
                                        'referral_bookings' => (string) ($attrs['referral_bookings'] ?? 0),
                                        'total_bookings' => (string) ($attrs['total_bookings'] ?? 0),
                                        'referrals_count' => (string) ($attrs['referrals_count'] ?? 0),
                                        'date_joined' => $record->user?->secured_at?->format('Y-m-d H:i:s') ?? '',
                                    ];
                                });
                            }

                            public function headings(): array
                            {
                                $dateFilter = $this->parent->data['date_filter'] ?? 'all_time';
                                $dateRangeText = 'All Time';

                                if ($dateFilter === 'date_range') {
                                    $startDate = $this->parent->data['start_date'] ?? null;
                                    $endDate = $this->parent->data['end_date'] ?? null;

                                    if ($startDate && $endDate) {
                                        $start = Carbon::parse($startDate)->format('M j, Y');
                                        $end = Carbon::parse($endDate)->format('M j, Y');
                                        $dateRangeText = "{$start} - {$end}";
                                    }
                                }

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
                                    "Total Earnings ({$dateRangeText})",
                                    "Direct Bookings ({$dateRangeText})",
                                    "Referral Bookings ({$dateRangeText})",
                                    "Total Bookings ({$dateRangeText})",
                                    'Referrals Count (All Time)',
                                    'Date Joined',
                                ];
                            }
                        };

                        // Generate filename based on filters
                        $filename = 'concierges-export-';
                        if ($dateFilter === 'date_range' && $startDate && $endDate) {
                            $start = Carbon::parse($startDate)->format('Y-m-d');
                            $end = Carbon::parse($endDate)->format('Y-m-d');
                            $filename .= "{$start}_to_{$end}-";
                        } else {
                            $filename .= 'all-time-';
                        }
                        $filename .= now()->format('Y-m-d-H-i-s').'.csv';

                        return Excel::download($export, $filename);
                    }),
            ])
            ->searchable(false)
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

                        $lastLogin = $concierge->last_login;
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

    protected function getConciergesQueryWithDateFilter(Builder $baseQuery, string $startDate, string $endDate): Builder
    {
        // This method will be called from the date filter to modify the earnings calculations
        // We need to replace the base query with date-filtered subqueries
        $earningTypes = [
            EarningType::CONCIERGE->value,
            EarningType::CONCIERGE_REFERRAL_1->value,
            EarningType::CONCIERGE_REFERRAL_2->value,
            EarningType::CONCIERGE_BOUNTY->value,
            EarningType::REFUND->value,
        ];

        return $baseQuery->select([
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
            // Referrals count subquery (always all-time)
            DB::raw('(SELECT COUNT(*) FROM concierges c2
                JOIN users u3 ON c2.user_id = u3.id
                WHERE u3.concierge_referral_id = concierges.id) as referrals_count'),
            // Date-filtered earnings calculations
            DB::raw("COALESCE((
                SELECT SUM(e.amount)
                FROM earnings e
                JOIN bookings b ON e.booking_id = b.id
                WHERE e.user_id = users.id
                AND b.confirmed_at IS NOT NULL
                AND b.confirmed_at BETWEEN '{$startDate}' AND '{$endDate}'
                AND e.type IN ('".implode("','", $earningTypes)."')
                AND b.status NOT IN ('refunded', 'partially_refunded')
            ), 0) as total_earnings"),
            // Date-filtered direct bookings
            DB::raw("COALESCE((
                SELECT COUNT(DISTINCT b.id)
                FROM earnings e
                JOIN bookings b ON e.booking_id = b.id
                WHERE e.user_id = users.id
                AND b.confirmed_at IS NOT NULL
                AND b.confirmed_at BETWEEN '{$startDate}' AND '{$endDate}'
                AND e.type IN ('concierge', 'concierge_bounty')
                AND e.type NOT IN ('refund')
                AND b.status NOT IN ('refunded', 'partially_refunded')
            ), 0) as direct_bookings"),
            // Date-filtered referral bookings
            DB::raw("COALESCE((
                SELECT COUNT(DISTINCT b.id)
                FROM earnings e
                JOIN bookings b ON e.booking_id = b.id
                WHERE e.user_id = users.id
                AND b.confirmed_at IS NOT NULL
                AND b.confirmed_at BETWEEN '{$startDate}' AND '{$endDate}'
                AND e.type IN ('concierge_referral_1', 'concierge_referral_2')
                AND b.status NOT IN ('refunded', 'partially_refunded')
            ), 0) as referral_bookings"),
            // Date-filtered total bookings
            DB::raw("COALESCE((
                SELECT COUNT(DISTINCT b.id)
                FROM earnings e
                JOIN bookings b ON e.booking_id = b.id
                WHERE e.user_id = users.id
                AND b.confirmed_at IS NOT NULL
                AND b.confirmed_at BETWEEN '{$startDate}' AND '{$endDate}'
                AND e.type IN ('".implode("','", $earningTypes)."')
                AND b.status NOT IN ('refunded', 'partially_refunded')
            ), 0) as total_bookings"),
        ]);
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = $this->getConciergesQuery();

        // Apply search filter
        if (filled($this->data['search'] ?? '')) {
            $search = strtolower((string) $this->data['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw('LOWER(users.first_name) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.last_name) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.email) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.phone) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(concierges.hotel_name) like ?', ["%{$search}%"]);
            });
        }

        // Apply date filter
        if (($this->data['date_filter'] ?? 'all_time') === 'date_range' &&
            filled($this->data['start_date'] ?? '') && filled($this->data['end_date'] ?? '')) {
            $startDate = Carbon::parse($this->data['start_date'])->startOfDay()->toDateTimeString();
            $endDate = Carbon::parse($this->data['end_date'])->endOfDay()->toDateTimeString();
            $query = $this->getConciergesQueryWithDateFilter($query, $startDate, $endDate);
        }

        return $query;
    }

    public function resetTable(): void
    {
        $this->resetPage();
    }

    public function getSubheading(): ?string
    {
        // Only show date range subheading if not all the time
        if (($this->data['date_filter'] ?? 'all_time') === 'date_range' &&
            filled($this->data['start_date'] ?? '') && filled($this->data['end_date'] ?? '')) {
            $startDate = Carbon::parse($this->data['start_date']);
            $endDate = Carbon::parse($this->data['end_date']);

            // Check if years are different
            if ($startDate->year === $endDate->year) {
                // Same year - don't show years
                $formattedStart = $startDate->format('M j');
                $formattedEnd = $endDate->format('M j');
            } else {
                // Different years - show years
                $formattedStart = $startDate->format('M j, Y');
                $formattedEnd = $endDate->format('M j, Y');
            }

            return "$formattedStart - $formattedEnd";
        }

        return null;
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
                    AND e.type IN (\''.implode('\',\'', $earningTypes).'\')
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
                // Total bookings (direct and referral)
                DB::raw('COALESCE((
                    SELECT COUNT(DISTINCT b.id)
                    FROM earnings e
                    JOIN bookings b ON e.booking_id = b.id
                    WHERE e.user_id = users.id
                    AND b.confirmed_at IS NOT NULL
                    AND e.type IN (\''.implode('\',\'', $earningTypes).'\')
                    AND b.status NOT IN (\'refunded\', \'partially_refunded\')
                ), 0) as total_bookings'),
                // Last login subquery
                DB::raw("(select max(login_at)
                    from authentication_log
                    where concierges.user_id = authentication_log.authenticatable_id
                      and authentication_log.authenticatable_type = 'App\\Models\\User') as last_login"),
            ])
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->whereNotNull('users.secured_at')
            ->with([
                'user.referral.referrer.partner',
                'user.referral.referrer.concierge',
                'user.referrer',
            ]);
    }

    private function getReferrerName($record): string
    {
        // Try different ways to get a referrer name
        if ($record->user?->referrer?->name ?? null) {
            return $record->user->referrer->name;
        }

        if ($record->user?->referral?->referrer?->name ?? null) {
            return $record->user->referral->referrer->name;
        }

        // Check attributes for referrer_first_name from the query
        return $record->getAttributes()['referrer_first_name'] ?? '';
    }

    private function getFormattedEarnings($record): string
    {
        // Try to get formatted earnings, fallback to manual calculation
        if (isset($record->formatted_total_earnings_in_u_s_d)) {
            return $record->formatted_total_earnings_in_u_s_d;
        }

        // Manual fallback calculation
        $totalEarnings = $record->total_earnings_in_u_s_d ?? 0;

        return '$'.number_format($totalEarnings / 100, 2);
    }
}
