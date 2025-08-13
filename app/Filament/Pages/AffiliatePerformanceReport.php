<?php

namespace App\Filament\Pages;

use App\Enums\BookingStatus;
use App\Enums\EarningType;
use App\Models\Concierge;
use App\Models\Region;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use App\Livewire\AffiliateMonthlyTrendsChart;
use App\Livewire\TopAffiliatesByBookingsChart;
use App\Livewire\TopAffiliatesByEarningsChart;

class AffiliatePerformanceReport extends Page implements HasTable
{
    use InteractsWithTable;

    /** @var array<string, mixed> */
    #[Url()]
    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $title = 'Affiliate Performance Report';

    protected static ?string $navigationLabel = 'Monthly Report';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.affiliate-performance-report';

    protected static ?string $navigationGroup = 'Affiliate Reporting';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasActiveRole('super_admin');
    }

    private function getMonthOptions(): array
    {
        $options = [];
        $currentDate = now()->startOfMonth();

        // Generate options for last 24 months and next 6 months
        for ($i = -24; $i <= 6; $i++) {
            $date = $currentDate->copy()->addMonths($i);
            $key = $date->format('Y-m');
            $label = $date->format('M Y');
            $options[$key] = $label;
        }

        return $options;
    }

    public function mount(): void
    {
        if (blank($this->data)) {
            $timezone = (string) (auth()->user()->timezone ?? config('app.default_timezone'));
            $this->data = [
                'startMonth' => now($timezone)->subMonths(5)->format('Y-m'),
                'numberOfMonths' => 6,
                'region' => '',
                'search' => '',
            ];
        }

        $this->tableFilters = $this->data ?? [];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 12;
    }

    protected function getHeaderWidgets(): array
    {
        $startMonth = (string) ($this->data['startMonth'] ?? now()->subMonths(5)->format('Y-m'));
        $numberOfMonths = (int) ($this->data['numberOfMonths'] ?? 6);
        $region = (string) ($this->data['region'] ?? '');
        $search = (string) ($this->data['search'] ?? '');

        return [
            AffiliateMonthlyTrendsChart::make([
                'startMonth' => $startMonth,
                'numberOfMonths' => $numberOfMonths,
                'region' => $region,
                'search' => $search,
            ]),
            TopAffiliatesByBookingsChart::make([
                'startMonth' => $startMonth,
                'numberOfMonths' => $numberOfMonths,
                'region' => $region,
                'search' => $search,
            ]),
            TopAffiliatesByEarningsChart::make([
                'startMonth' => $startMonth,
                'numberOfMonths' => $numberOfMonths,
                'region' => $region,
                'search' => $search,
            ]),
        ];
    }

    public function getHeading(): string|Htmlable
    {
        return 'Affiliate Performance Report';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! isset($this->data['startMonth'], $this->data['numberOfMonths'])) {
            return null;
        }

        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $startMonth = (string) $this->data['startMonth'];
        $numberOfMonths = (int) $this->data['numberOfMonths'];

        $startDate = Carbon::parse($startMonth . '-01', $timezone);
        $endDate = $startDate->copy()->addMonths($numberOfMonths)->subDay();

        $formattedStartDate = $startDate->format('M Y');
        $formattedEndDate = $endDate->format('M Y');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('search')
                    ->label('Search Affiliates')
                    ->placeholder('Search by name, email, or phone')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function () {
                        $this->resetTable();
                        $this->dispatch('$refresh');
                    }),
                Select::make('region')
                    ->label('Region')
                    ->options(
                        array_merge(
                            ['' => 'All Regions'],
                            Region::active()->pluck('name', 'id')->toArray()
                        )
                    )
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->resetTable();
                        $this->dispatch('$refresh');
                    }),
                Select::make('startMonth')
                    ->label('Start Month')
                    ->options($this->getMonthOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->resetTable();
                        $this->dispatch('$refresh');
                    }),
                Select::make('numberOfMonths')
                    ->label('Number of Months')
                    ->options(collect(range(1, 12))->mapWithKeys(fn($i) => [$i => (string) $i])->toArray())
                    ->default(6)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        // When months change, adjust start month to maintain recent period
                        $timezone = (string) (auth()->user()->timezone ?? config('app.default_timezone'));
                        $newStartMonth = now($timezone)->subMonths((int)$state - 1)->format('Y-m');
                        $this->data['startMonth'] = $newStartMonth;
                        $this->resetTable();
                        $this->dispatch('$refresh');
                    }),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $startMonth = (string) ($this->data['startMonth'] ?? now($timezone)->subMonths(5)->format('Y-m'));
        $numberOfMonths = (int) ($this->data['numberOfMonths'] ?? 6);
        $region = (string) ($this->data['region'] ?? '');
        $search = (string) ($this->data['search'] ?? '');

        // Calculate date range
        $startDate = Carbon::parse($startMonth . '-01', $timezone)->startOfDay()->setTimezone('UTC');
        $endDate = $startDate->copy()->addMonths($numberOfMonths)->subSecond();

                        // Generate monthly columns data
        $monthlyColumns = [];
        $monthlySelects = [];
        $currentDate = Carbon::parse($startMonth . '-01', $timezone);

        for ($i = 0; $i < $numberOfMonths; $i++) {
            $monthStart = $currentDate->copy()->startOfDay()->setTimezone('UTC');
            $monthEnd = $currentDate->copy()->endOfMonth()->endOfDay()->setTimezone('UTC');
            $monthKey = $currentDate->format('Y_m');
            $monthLabel = $currentDate->format('M Y');

            $monthlyColumns[] = [
                'key' => $monthKey,
                'label' => $monthLabel,
                'start' => $monthStart,
                'end' => $monthEnd,
                        ];

                        // Add SQL selects for this month's bookings and earnings
            $monthlySelects[] = "SUM(CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' THEN earnings.amount ELSE 0 END) as earnings_{$monthKey}";

                        // Separate direct and referral bookings - use COALESCE to ensure 0 instead of NULL
            $monthlySelects[] = "COALESCE(COUNT(DISTINCT CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' AND earnings.type IN ('concierge', 'concierge_bounty') THEN earnings.booking_id END), 0) as direct_bookings_{$monthKey}";
            $monthlySelects[] = "COALESCE(COUNT(DISTINCT CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' AND earnings.type IN ('concierge_referral_1', 'concierge_referral_2') THEN earnings.booking_id END), 0) as referral_bookings_{$monthKey}";

            // Separate direct and referral earnings - use COALESCE to ensure 0 instead of NULL
            $monthlySelects[] = "COALESCE(SUM(CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' AND earnings.type IN ('concierge', 'concierge_bounty') THEN earnings.amount ELSE 0 END), 0) as direct_earnings_{$monthKey}";
            $monthlySelects[] = "COALESCE(SUM(CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' AND earnings.type IN ('concierge_referral_1', 'concierge_referral_2') THEN earnings.amount ELSE 0 END), 0) as referral_earnings_{$monthKey}";

            // Keep total bookings for backward compatibility
            $monthlySelects[] = "COUNT(DISTINCT CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' THEN earnings.booking_id END) as bookings_{$monthKey}";

            $currentDate->addMonth();
        }

        // Build base query using proven patterns from ConciergeOverallLeaderboard
        $conciergeEarningTypes = [
            EarningType::CONCIERGE,
            EarningType::CONCIERGE_REFERRAL_1,
            EarningType::CONCIERGE_REFERRAL_2,
            EarningType::CONCIERGE_BOUNTY,
        ];

        $baseQuery = Concierge::query()
            ->select(array_merge([
                'concierges.id',
                'concierges.user_id',
                'concierges.hotel_name',
                DB::raw('SUM(earnings.amount) as total_earnings'),
                DB::raw('MAX(earnings.currency) as currency'),
                DB::raw('COUNT(DISTINCT earnings.booking_id) as total_bookings'),
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
            ], array_map(fn($select) => DB::raw($select), $monthlySelects)))
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->join('earnings', 'earnings.user_id', '=', 'users.id')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
                BookingStatus::PARTIALLY_REFUNDED,
                BookingStatus::NO_SHOW,
                BookingStatus::CANCELLED,
            ])
            ->whereIn('earnings.type', array_map(fn($type) => $type->value, $conciergeEarningTypes))
            ->when($region, function (Builder $query) use ($region) {
                $query->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
                      ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
                      ->where('venues.region', $region);
            })
            ->when($search, function (Builder $query) use ($search) {
                $searchLower = strtolower($search);
                $query->where(function (Builder $q) use ($searchLower) {
                    $q->whereRaw('LOWER(users.first_name) like ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(users.last_name) like ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(users.email) like ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(users.phone) like ?', ["%{$searchLower}%"]);
                });
            })
            ->groupBy('concierges.id', 'concierges.user_id', 'concierges.hotel_name', 'users.first_name', 'users.last_name')
                ->havingRaw('SUM(earnings.amount) > 0');

        return $table
            ->query($baseQuery->with(['user']))
            ->columns(array_merge([
                TextColumn::make('user_name')
                    ->label('Affiliate')
                    ->html()
                    ->formatStateUsing(function (Concierge $record) {
                        $user = $record->user;
                        if (!$user) {
                            return new HtmlString('<div class="text-gray-400">Unknown User</div>');
                        }

                        $name = $user->first_name . ' ' . $user->last_name;
                        $company = $record->hotel_name ?: 'N/A';

                        return new HtmlString(sprintf(
                            '<div class="space-y-1"><div class="font-medium">%s</div><div class="text-xs text-gray-500">%s</div></div>',
                            e($name),
                            e($company)
                        ));
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $searchLower = strtolower($search);
                        return $query->whereHas('user', function (Builder $query) use ($searchLower) {
                            $query->whereRaw('LOWER(users.first_name) like ?', ["%{$searchLower}%"])
                                  ->orWhereRaw('LOWER(users.last_name) like ?', ["%{$searchLower}%"]);
                        });
                    })
                    ->sortable(),
            ], array_map(function ($monthData) {
                $monthKey = $monthData['key'];
                $monthLabel = $monthData['label'];

                return TextColumn::make("bookings_{$monthKey}")
                    ->label($monthLabel)
                    ->alignCenter()
                                                                ->formatStateUsing(function (Concierge $record) use ($monthKey) {
                            $totalBookings = (int) ($record->getAttributes()["bookings_{$monthKey}"] ?? 0);
                            $totalEarnings = (int) ($record->getAttributes()["earnings_{$monthKey}"] ?? 0);
                            $currency = (string) ($record->getAttributes()['currency'] ?? 'USD');

                            if ($totalBookings === 0 && $totalEarnings === 0) {
                                return new HtmlString('<div class="space-y-1"><div class="text-xs font-medium">0</div><div class="text-xs text-gray-600">$0.00</div></div>');
                            }

                            return new HtmlString(sprintf(
                                '<div class="space-y-1"><div class="text-xs font-medium">%s</div><div class="text-xs text-gray-600">%s</div></div>',
                                $totalBookings,
                                money($totalEarnings, $currency)
                            ));
                        })
                    ->html()
                    ->sortable();
            }, $monthlyColumns), [
                TextColumn::make('total_bookings')
                    ->label('Total Bookings')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('total_earnings')
                    ->label('Total Earnings')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(function (Concierge $record) {
                        $earnings = (int) ($record->getAttributes()['total_earnings'] ?? 0);
                        $currency = $record->getAttributes()['currency'] ?? 'USD';
                        return money($earnings, (string) $currency);
                    }),
            ]))
            ->defaultSort('total_earnings', 'desc')
            ->striped()
            ->searchable(false)
            ->headerActions([
                $this->getExportAction($startMonth, $numberOfMonths, $baseQuery),
            ]);
    }

        private function getExportAction(string $startMonth, int $numberOfMonths, $baseQuery): Action
    {
        return Action::make('export')
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () use ($startMonth, $numberOfMonths, $baseQuery) {
                $timezone = (string) (auth()->user()->timezone ?? config('app.default_timezone'));
                $startDate = Carbon::parse($startMonth . '-01', $timezone);
                $endDate = $startDate->copy()->addMonths($numberOfMonths)->subDay();
                $dateRange = $startDate->format('Y-m') . '_to_' . $endDate->format('Y-m');

                // Get the same data that the table uses
                $data = $baseQuery->with(['user'])->get();

                $export = new class($data, $startMonth, $numberOfMonths, $timezone) implements FromCollection, WithHeadings {
                    public function __construct(
                        private $data,
                        private string $startMonth,
                        private int $numberOfMonths,
                        private string $timezone
                    ) {}

                    public function collection()
                    {
                        return $this->data->map(function ($record) {
                            $row = [
                                // Basic info
                                'first_name' => $record->user?->first_name ?? '',
                                'last_name' => $record->user?->last_name ?? '',
                                'hotel_name' => $record->hotel_name ?? '',
                            ];

                                                                                                                                            // Add monthly data with separate direct/referral columns
                            $currentDate = Carbon::parse($this->startMonth . '-01', $this->timezone);
                            for ($i = 0; $i < $this->numberOfMonths; $i++) {
                                $monthKey = $currentDate->format('Y_m');
                                $attributes = $record->getAttributes();

                                // Force to integer and ensure 0 for any null/empty values
                                $directBookings = (int) ($attributes["direct_bookings_{$monthKey}"] ?? 0);
                                $referralBookings = (int) ($attributes["referral_bookings_{$monthKey}"] ?? 0);
                                $directEarnings = (int) ($attributes["direct_earnings_{$monthKey}"] ?? 0);
                                $referralEarnings = (int) ($attributes["referral_earnings_{$monthKey}"] ?? 0);
                                $currency = (string) ($attributes['currency'] ?? 'USD');

                                // Explicitly set values as strings - ensure no blanks in CSV
                                $row["direct_bookings_{$monthKey}"] = (string) $directBookings;
                                $row["referral_bookings_{$monthKey}"] = (string) $referralBookings;
                                $row["direct_earnings_{$monthKey}"] = $directEarnings > 0 ?
                                    number_format($directEarnings / 100, 2) . ' ' . $currency :
                                    '0.00 ' . $currency;
                                $row["referral_earnings_{$monthKey}"] = $referralEarnings > 0 ?
                                    number_format($referralEarnings / 100, 2) . ' ' . $currency :
                                    '0.00 ' . $currency;

                                $currentDate->addMonth();
                            }

                            // Add totals and contact info
                            $totalEarnings = (int) ($record->getAttributes()['total_earnings'] ?? 0);
                            $currency = (string) ($record->getAttributes()['currency'] ?? 'USD');

                            $row['total_bookings'] = (string) ((int) ($record->getAttributes()['total_bookings'] ?? 0));
                            $row['total_earnings'] = $totalEarnings > 0 ?
                                number_format($totalEarnings / 100, 2) . ' ' . $currency :
                                '0.00 ' . $currency;
                            $row['email'] = $record->user?->email ?? '';
                            $row['phone'] = $record->user?->phone ?? '';
                            $row['city'] = $record->user?->city ?? '';
                            $row['state'] = $record->user?->state ?? '';
                            $row['zip'] = $record->user?->zip ?? '';
                            $row['created_at'] = $record->user?->created_at?->format('Y-m-d') ?? '';

                            return $row;
                        });
                    }

                    public function headings(): array
                    {
                        $headers = ['First Name', 'Last Name', 'Hotel / Company'];

                        // Add monthly headers with separate direct/referral columns
                        $currentDate = Carbon::parse($this->startMonth . '-01', $this->timezone);
                        for ($i = 0; $i < $this->numberOfMonths; $i++) {
                            $monthLabel = $currentDate->format('M Y');
                            $headers[] = "{$monthLabel} Direct Bookings";
                            $headers[] = "{$monthLabel} Referral Bookings";
                            $headers[] = "{$monthLabel} Direct Earnings";
                            $headers[] = "{$monthLabel} Referral Earnings";
                            $currentDate->addMonth();
                        }

                        // Add total and contact headers
                        $headers = array_merge($headers, [
                            'Total Bookings',
                            'Total Earnings',
                            'Email',
                            'Phone',
                            'City',
                            'State',
                            'Zip',
                            'Date Signed Up'
                        ]);

                        return $headers;
                    }
                };

                return Excel::download($export, "Affiliate-Performance-Report-{$dateRange}.csv");
            });
    }

    private function getTableQuery()
    {
        $timezone = (string) (auth()->user()->timezone ?? config('app.default_timezone'));
        $startMonth = (string) ($this->data['startMonth'] ?? now($timezone)->subMonths(5)->format('Y-m'));
        $numberOfMonths = (int) ($this->data['numberOfMonths'] ?? 6);
        $region = (string) ($this->data['region'] ?? '');
        $search = (string) ($this->data['search'] ?? '');

        // Calculate date range
        $startDate = Carbon::parse($startMonth . '-01', $timezone)->startOfDay()->setTimezone('UTC');
        $endDate = $startDate->copy()->addMonths($numberOfMonths)->subSecond();

        // Generate monthly columns data
        $monthlySelects = [];
        $currentDate = Carbon::parse($startMonth . '-01', $timezone);

        for ($i = 0; $i < $numberOfMonths; $i++) {
            $monthStart = $currentDate->copy()->startOfDay()->setTimezone('UTC');
            $monthEnd = $currentDate->copy()->endOfMonth()->endOfDay()->setTimezone('UTC');
            $monthKey = $currentDate->format('Y_m');

            // Add SQL selects for this month's bookings and earnings
            $monthlySelects[] = "SUM(CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' THEN earnings.amount ELSE 0 END) as earnings_{$monthKey}";
            $monthlySelects[] = "COUNT(DISTINCT CASE WHEN bookings.confirmed_at BETWEEN '{$monthStart->toDateTimeString()}' AND '{$monthEnd->toDateTimeString()}' THEN earnings.booking_id END) as bookings_{$monthKey}";

            $currentDate->addMonth();
        }

        // Build base query using proven patterns from ConciergeOverallLeaderboard
        $conciergeEarningTypes = [
            EarningType::CONCIERGE,
            EarningType::CONCIERGE_REFERRAL_1,
            EarningType::CONCIERGE_REFERRAL_2,
            EarningType::CONCIERGE_BOUNTY,
        ];

        return Concierge::query()
            ->select(array_merge([
                'concierges.id',
                'concierges.user_id',
                'concierges.hotel_name',
                DB::raw('SUM(earnings.amount) as total_earnings'),
                DB::raw('MAX(earnings.currency) as currency'),
                DB::raw('COUNT(DISTINCT earnings.booking_id) as total_bookings'),
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
            ], array_map(fn($select) => DB::raw($select), $monthlySelects)))
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->join('earnings', 'earnings.user_id', '=', 'users.id')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereBetween('bookings.confirmed_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
                BookingStatus::PARTIALLY_REFUNDED,
                BookingStatus::NO_SHOW,
                BookingStatus::CANCELLED,
            ])
            ->whereIn('earnings.type', array_map(fn($type) => $type->value, $conciergeEarningTypes))
            ->when($region, function (Builder $query) use ($region) {
                $query->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
                      ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
                      ->where('venues.region', $region);
            })
            ->when($search, function (Builder $query) use ($search) {
                $searchLower = strtolower($search);
                $query->where(function (Builder $q) use ($searchLower) {
                    $q->whereRaw('LOWER(users.first_name) like ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(users.last_name) like ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(users.email) like ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(users.phone) like ?', ["%{$searchLower}%"]);
                });
            })
            ->groupBy('concierges.id', 'concierges.user_id', 'concierges.hotel_name', 'users.first_name', 'users.last_name')
            ->havingRaw('SUM(earnings.amount) > 0')
            ->with(['user']);
    }
}
