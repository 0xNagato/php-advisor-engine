<?php

namespace App\Filament\Pages;

use App\Enums\BookingStatus;
use App\Enums\EarningType;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use App\Models\VenueInvoice;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class PaymentExports extends Page implements HasTable
{
    use InteractsWithTable;

    #[Url()]
    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.payment-exports';

    protected static ?string $navigationGroup = 'Payments';

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public function mount(): void
    {
        if (blank($this->data)) {
            $this->form->fill([
                'startDate' => now()->timezone(auth()->user()->timezone)->subDays(30)->format('Y-m-d'),
                'endDate' => now()->timezone(auth()->user()->timezone)->format('Y-m-d'),
                'name_search' => '',
                'user_type' => [],
                'min_amount' => '',
                'max_amount' => '',
                'role' => 'venue',
            ]);
        }

        $this->tableFilters = $this->data;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(4)
                    ->schema([
                        TextInput::make('name_search')
                            ->label('Name Search')
                            ->placeholder('Search by name')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function () {
                                $this->resetTable();
                            }),
                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'venue' => 'Venue',
                                'concierge' => 'Concierge',
                                'partner' => 'Partner',
                                'venue_group' => 'Venue Group',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->resetTable();
                            }),
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->default(now()->subDays(30))
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->resetTable();
                            }),
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->resetTable();
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Get invoice actions for the table
     */
    protected function getInvoiceActions(): array
    {
        $role = $this->data['role'] ?? 'venue';
        $startDate = $this->data['startDate'];
        $endDate = $this->data['endDate'];

        // ----- Venue rows -----
        if ($role === 'venue') {
            return [
                ActionGroup::make([
                    Action::make('download')
                        ->label('Download Invoice')
                        ->icon('heroicon-m-arrow-down-on-square')
                        ->color('indigo')
                        ->visible(fn (Venue $venue) => $this->hasExistingVenueInvoice($venue, $startDate, $endDate))
                        ->action(fn (Venue $venue) => $this->downloadVenueInvoiceForVenue($venue)),
                    Action::make('generate')
                        ->label('Generate Invoice')
                        ->icon('heroicon-m-document-plus')
                        ->color('indigo')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Venue $venue) => "Generate Invoice for {$venue->name}")
                        ->modalDescription(fn (Venue $venue) => $this->getVenueInvoiceModalDescriptionForVenue($venue))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoice Generated')
                                ->body('The invoice has been generated and will open in a new tab.')
                        )
                        ->action(fn (Venue $venue) => $this->generateVenueInvoiceForVenue($venue)),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Invoice Actions'),
            ];
        }

        // ----- Venue group rows -----
        if ($role === 'venue_group') {
            return [
                ActionGroup::make([
                    Action::make('downloadGroupInvoice')
                        ->label('Download Group Invoice')
                        ->icon('heroicon-m-arrow-down-on-square')
                        ->color('indigo')
                        ->visible(fn (User $user) => $this->hasExistingVenueGroupInvoice($user, $startDate, $endDate))
                        ->action(fn (User $user) => $this->downloadVenueGroupInvoice($user)),
                    Action::make('generateGroupInvoice')
                        ->label('Generate Group Invoice')
                        ->icon('heroicon-m-document-plus')
                        ->color('indigo')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $user) => $this->getVenueGroupModalHeading($user))
                        ->modalDescription(function (User $user) {
                            $userTimezone = auth()->user()->timezone ?? config('app.timezone');

                            return $this->getVenueGroupInvoiceModalDescription($user, $userTimezone);
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Group Invoice Generated')
                                ->body('The group invoice has been generated and will open in a new tab.')
                        )
                        ->action(fn (User $user) => $this->generateVenueGroupInvoice($user)),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Invoice Actions'),
            ];
        }

        // concierge / partner – no invoice actions
        return [];
    }

    /**
     * Helper to format heading based on current date range
     */
    private function headingDates(string $tz): string
    {
        $start = Carbon::parse($this->data['startDate'], $tz)->format('M j, y');
        $end = Carbon::parse($this->data['endDate'], $tz)->format('M j, y');

        return "Earnings: $start - $end";
    }

    /**
     * Column set for Venue rows
     */
    private function venueColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Venue')
                ->html()
                ->formatStateUsing(function (Venue $venue): string {
                    // Determine subtext: Venue Group name if exists, otherwise Owner name
                    $subtext = $venue->venueGroup ?
                        "Group: {$venue->venueGroup->name}" :
                        ($venue->user?->name ?? 'Owner Unknown');

                    return "<div class='flex flex-col gap-1'><span>{$venue->name}</span><span class='text-[10px] sm:text-xs text-gray-500'>{$subtext}</span></div>";
                }),
            TextColumn::make('bookings_count')
                ->label('Bookings')
                ->url(function (Venue $venue): string {
                    $filters = [
                        // Match filters expected by BookingSearch.php
                        'venue_search' => $venue->name,
                        'start_date' => $this->data['startDate'],
                        'end_date' => $this->data['endDate'],
                        'status' => BookingStatus::PAYOUT_STATUSES,
                        'show_booking_time' => true,
                    ];

                    // Use the route name for the BookingSearch page
                    return route('filament.admin.pages.booking-search', ['filters' => $filters]);
                })
                ->sortable()
                ->color('indigo')
                ->extraAttributes(['class' => 'underline decoration-indigo-600']),
            TextColumn::make('total_earnings')
                ->label('Earnings')
                ->sortable()
                ->formatStateUsing(fn ($record) => money($record->total_earnings,
                    $record->currency).' '.$record->currency),
        ];
    }

    /** Column set for Group rows – reuse existing logic */
    private function groupColumns(): array
    {
        return [
            TextColumn::make('name') // This will be the User's name, but formatted to show group info
                ->label('Venue Group')
                ->sortable(['first_name', 'last_name'])
                ->html()
                ->formatStateUsing(function (User $record): string {
                    if ($record->primaryManagedVenueGroups->isNotEmpty()) {
                        $venueGroup = $record->primaryManagedVenueGroups->first();
                        $venuesCollection = $venueGroup->venues;
                        $venueCount = $venuesCollection->count();

                        $groupName = $venueGroup->name;

                        if ($venueCount <= 3) {
                            $venues = $venuesCollection->pluck('name')->implode(', ');
                            $subtext = $venues;
                        } else {
                            $shownVenues = $venuesCollection->take(3)->pluck('name')->implode(', ');
                            $remainingCount = $venueCount - 3;
                            $subtext = "{$shownVenues} +{$remainingCount} more";
                        }

                        return new HtmlString(
                            "<div class='flex flex-col gap-1'><span>{$groupName}</span>".
                            ($subtext ? "<span class='text-[10px] sm:text-xs text-gray-500'>{$subtext}</span>" : '').
                            '</div>'
                        );
                    }

                    // Fallback for safety, though this column should only appear for group managers
                    return $record->name;
                }),
            TextColumn::make('bookings_count')
                ->label('Bookings')
                ->url(function (User $user): string {
                    $venueGroup = $user->primaryManagedVenueGroups->first();
                    $venueIds = $venueGroup ? $venueGroup->venues->pluck('id')->toArray() : [];

                    $filters = [
                        // Match filters expected by BookingSearch.php
                        'venue_search' => $venueGroup?->name ?? '',
                        'start_date' => $this->data['startDate'],
                        'end_date' => $this->data['endDate'],
                        'status' => BookingStatus::PAYOUT_STATUSES,
                        'show_booking_time' => true,
                    ];

                    return route('filament.admin.pages.booking-search', ['filters' => $filters]);
                })
                ->sortable()
                ->color('indigo')
                ->extraAttributes(['class' => 'underline decoration-indigo-600']),
            TextColumn::make('total_earnings')
                ->label('Earnings')
                ->sortable()
                ->formatStateUsing(fn ($record) => money($record->total_earnings,
                    $record->currency).' '.$record->currency),
        ];
    }

    /** Column set for user rows (concierge / partner) using existing formatter */
    private function userColumns(): array
    {
        // reuse previous complex column definition; fetch via existing method later in table builder
        return [
            TextColumn::make('name')
                ->label('Name')
                ->sortable(['first_name', 'last_name'])
                ->color('primary')
                ->size('xs')
                ->wrap()
                ->html()
                ->formatStateUsing(fn (User $record) => $record->name),
            TextColumn::make('bookings_count')
                ->label('Bookings')
                ->url(function (User $user): string {
                    $filters = [
                        // Match filters expected by BookingSearch.php
                        ($user->hasRole('concierge') ? 'concierge_search' : 'partner_search') => $user->name,
                        // Pass user name to search filters
                        'start_date' => $this->data['startDate'],
                        'end_date' => $this->data['endDate'],
                        'status' => BookingStatus::PAYOUT_STATUSES,
                        'show_booking_time' => true,
                    ];

                    return route('filament.admin.pages.booking-search', ['filters' => $filters]);
                })
                ->sortable()
                ->color('indigo')
                ->extraAttributes(['class' => 'underline decoration-indigo-600']),
            TextColumn::make('total_earnings')
                ->label('Earnings')
                ->sortable()
                ->formatStateUsing(fn ($record) => money($record->total_earnings,
                    $record->currency).' '.$record->currency),
        ];
    }

    // -----------------------------------------------------------------------------------------
    //  TABLE BUILDER – role‑aware
    // -----------------------------------------------------------------------------------------

    public function table(Table $table): Table
    {
        $tz = auth()->user()->timezone ?? config('app.timezone');
        $role = $this->data['role'] ?? 'venue';

        $startUtc = Carbon::parse($this->data['startDate'], $tz)->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($this->data['endDate'], $tz)->endOfDay()->setTimezone('UTC');
        $search = $this->data['name_search'] ?? null;

        // ------------------------ VENUE ------------------------
        if ($role === 'venue') {
            $venueTotals = Venue::query()
                ->join('users', 'users.id', '=', 'venues.user_id')
                ->join('schedule_templates as st', 'st.venue_id', '=', 'venues.id')
                ->join('bookings as b', 'b.schedule_template_id', '=', 'st.id')
                ->join('earnings as e', 'e.booking_id', '=', 'b.id')
                ->whereColumn('e.user_id', 'users.id')
                ->whereIn('b.status', BookingStatus::PAYOUT_STATUSES)
                ->whereIn('e.type', [EarningType::VENUE->value, EarningType::VENUE_PAID->value])
                ->when($search, function ($q) use ($search) {
                    $search = strtolower((string) $search);
                    $q->where(function ($qq) use ($search) {
                        $qq->whereRaw('LOWER(venues.name) like ?', ["%$search%"])
                            ->orWhereRaw('LOWER(users.first_name) like ?', ["%$search%"])
                            ->orWhereRaw('LOWER(users.last_name) like ?', ["%$search%"]);
                    });
                })
                ->whereBetween('b.booking_at_utc', [$startUtc, $endUtc])
                ->groupBy('venues.id', 'e.currency')
                ->selectRaw('venues.*, e.currency, SUM(e.amount) as total_earnings, COUNT(DISTINCT e.booking_id) as bookings_count');

            $builder = Venue::query()->fromSub($venueTotals, 'venues')->with(['user', 'venueGroup']);

            return $table
                ->query($builder)
                ->columns($this->venueColumns())
                ->actions($this->getInvoiceActions())
                ->heading($this->headingDates($tz))
                ->defaultSort('total_earnings', 'desc')
                ->paginated([10, 25, 50, 100])
                ->filters([
                    SelectFilter::make('venue_group_id')
                        ->label('Venue Group')
                        ->options(VenueGroup::query()->pluck('name', 'id'))
                        ->searchable(),
                    // The query modification is automatically handled by Filament
                    // based on the column name ('venue_group_id')
                ])
                ->headerActions($this->getExportHeaderActions('venue'));
        }

        // ------------------------ VENUE GROUP ------------------------
        if ($role === 'venue_group') {
            // Re‑use the earlier groupManagersQuery construction
            $groupManagersQuery = User::query()
                ->join('venue_groups', 'users.id', '=', 'venue_groups.primary_manager_id')
                ->leftJoin(DB::raw("(
                    SELECT v.venue_group_id,
                           SUM(CASE WHEN b.is_prime = true AND e.type = '".EarningType::VENUE->value."' THEN e.amount
                                    WHEN b.is_prime = false AND e.type = '".EarningType::VENUE_PAID->value."' THEN -ABS(e.amount)
                                    ELSE 0 END)               as total_earnings,
                           COUNT(DISTINCT e.booking_id)      as bookings_count,
                           e.currency
                    FROM venues v
                    JOIN schedule_templates st ON st.venue_id = v.id
                    JOIN bookings b            ON b.schedule_template_id = st.id
                    JOIN earnings e            ON e.booking_id = b.id AND e.user_id = (
                        SELECT vg.primary_manager_id FROM venue_groups vg WHERE vg.id = v.venue_group_id
                    )
                    WHERE b.status IN ('".implode("','",
                    array_map(fn ($s) => $s->value, BookingStatus::PAYOUT_STATUSES))."')
                      AND e.type IN ('".implode("','", [EarningType::VENUE->value, EarningType::VENUE_PAID->value])."')
                      AND b.booking_at_utc BETWEEN '".$startUtc->format('Y-m-d H:i:s')."' AND '".$endUtc->format('Y-m-d H:i:s')."'
                    GROUP BY v.venue_group_id, e.currency
                ) as venue_earnings"), 'venue_groups.id', '=', 'venue_earnings.venue_group_id')
                ->select([
                    'users.*',
                    'venue_groups.name as venue_group_name',
                    'venue_earnings.currency',
                    'venue_earnings.total_earnings',
                    'venue_earnings.bookings_count',
                    'venue_groups.id as venue_group_id',
                ])
                ->when($search, function (Builder $query) use ($search) {
                    // Search both primary manager name and venue group name
                    $search = strtolower((string) $search);
                    $query->where(function ($q) use ($search) {
                        $q->whereRaw('LOWER(users.first_name) like ?', ["%$search%"])
                            ->orWhereRaw('LOWER(users.last_name) like ?', ["%$search%"])
                            ->orWhereRaw('LOWER(venue_groups.name) like ?', ["%$search%"]);
                    });
                })
                ->whereNotNull('venue_earnings.bookings_count');

            $builder = User::query()->fromSub($groupManagersQuery, 'users')->with(['primaryManagedVenueGroups.venues']);

            return $table
                ->query($builder)
                ->columns($this->groupColumns())
                ->actions($this->getInvoiceActions())
                ->heading($this->headingDates($tz))
                ->defaultSort('total_earnings', 'desc')
                ->paginated([10, 25, 50, 100])
                ->headerActions($this->getExportHeaderActions('venue_group'));
        }

        // ------------------------ CONCIERGE / PARTNER ------------------------
        // Re‑use simplified individualUsersQuery
        $userQuery = User::query()
            ->join('earnings', 'users.id', '=', 'earnings.user_id')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', BookingStatus::PAYOUT_STATUSES)
            ->whereBetween('bookings.booking_at_utc', [$startUtc, $endUtc])
            ->select([
                'users.*',
                'earnings.currency',
                DB::raw('SUM(earnings.amount) as total_earnings'),
                DB::raw('COUNT(DISTINCT earnings.booking_id) as bookings_count'),
            ])
            ->when($role === 'concierge', fn ($q) => $q->has('concierge'))
            ->when($role === 'partner', fn ($q) => $q->has('partner'))
            ->groupBy('users.id', 'earnings.currency');

        $builder = User::query()
            ->fromSub($userQuery, 'users')
            ->with(['concierge']); // Eager load concierge for export formatting

        return $table
            ->query($builder)
            ->columns($this->userColumns())
            ->actions($this->getInvoiceActions())
            ->heading($this->headingDates($tz))
            ->defaultSort('total_earnings', 'desc')
            ->paginated([10, 25, 50, 100])
            ->headerActions($this->getExportHeaderActions($role));
    }

    protected function hasExistingVenueInvoice(Venue $venue, string $startDate, string $endDate): bool
    {
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');
        $startDateCarbon = Carbon::parse($startDate, $userTimezone);
        $endDateCarbon = Carbon::parse($endDate, $userTimezone);

        return VenueInvoice::query()
            ->where('venue_id', $venue->id)
            ->whereNull('venue_group_id')
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->exists();
    }

    protected function hasExistingVenueGroupInvoice(User $user, string $startDate, string $endDate): bool
    {
        $venueGroup = $user->primaryManagedVenueGroups()->first();
        if (! $venueGroup) {
            return false;
        }

        $userTimezone = auth()->user()->timezone ?? config('app.timezone');
        $startDateCarbon = Carbon::parse($startDate, $userTimezone);
        $endDateCarbon = Carbon::parse($endDate, $userTimezone);

        return VenueInvoice::query()
            ->where('venue_group_id', $venueGroup->id)
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->exists();
    }

    protected function downloadVenueInvoiceForVenue(Venue $venue): void
    {
        // Ensure venue owner exists (needed for the current route structure)
        if (! $venue->user) {
            Notification::make()
                ->warning()
                ->title('Venue Owner Not Found')
                ->body('Cannot download invoice because the venue owner could not be found.')
                ->send();

            return;
        }

        $url = route('venue.invoice.download', [
            'venue' => $venue->id, // Use venue_id as per route definition
            'startDate' => $this->data['startDate'],
            'endDate' => $this->data['endDate'],
        ]);

        $this->js("window.open('$url', '_blank')");
    }

    protected function generateVenueInvoiceForVenue(Venue $venue): void
    {
        // Ensure venue owner exists
        if (! $venue->user) {
            Notification::make()
                ->warning()
                ->title('Venue Owner Not Found')
                ->body('Cannot generate invoice because the venue owner could not be found.')
                ->send();

            return;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($venue)
            ->withProperties([
                'date_range' => [
                    'start' => $this->data['startDate'],
                    'end' => $this->data['endDate'],
                ],
                'generated_by' => auth()->user()->name,
                'generated_at' => now(),
            ])
            ->log('Generated venue invoice (venue table row)');

        $url = route('venue.invoice.download', [
            'venue' => $venue->id, // Use venue_id as per route definition
            'startDate' => $this->data['startDate'],
            'endDate' => $this->data['endDate'],
            'regenerate' => true, // Force regeneration of the invoice
        ]);

        $this->js("window.open('$url', '_blank')");
    }

    protected function getVenueInvoiceModalDescriptionForVenue(Venue $venue): HtmlString
    {
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Check for any overlapping invoices for this specific venue
        $overlappingInvoices = $this->getOverlappingInvoices($venue->id, null, $startDateCarbon, $endDateCarbon);
        $warningHtml = $this->generateOverlappingInvoicesWarning($overlappingInvoices, 'Overlapping Invoices Found');

        // Changed from nowdoc ('HTML') to heredoc (HTML) to allow variable interpolation
        $standardHtml = <<<HTML
            <div class="space-y-2 text-sm text-gray-600">
                <p>This will generate an invoice for all bookings for <strong>{$venue->name}</strong> in the selected date range. The process may take a few moments.</p>
                <p class="p-1 text-xs font-semibold text-gray-600 bg-gray-100 border border-gray-300 rounded-md">
                    This action will be logged for audit purposes.
                </p>
            </div>
        HTML;

        return new HtmlString($warningHtml.$standardHtml);
    }

    protected function downloadVenueGroupInvoice(User $record): void
    {
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        if (! $venueGroup) {
            return;
        }

        // Just pass the dates directly to the route
        $url = route('venue-group.invoice.download', [
            'venueGroup' => $venueGroup->id,
            'startDate' => $this->data['startDate'],
            'endDate' => $this->data['endDate'],
        ]);

        $this->js("window.open('$url', '_blank')");
    }

    protected function generateVenueGroupInvoice(User $record): void
    {
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        if (! $venueGroup) {
            return;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($venueGroup)
            ->withProperties([
                'date_range' => [
                    'start' => $this->data['startDate'],
                    'end' => $this->data['endDate'],
                ],
                'generated_by' => auth()->user()->name,
                'generated_at' => now(),
            ])
            ->log('Generated venue group invoice');

        $url = route('venue-group.invoice.download', [
            'venueGroup' => $venueGroup->id,
            'startDate' => $this->data['startDate'],
            'endDate' => $this->data['endDate'],
            'regenerate' => true, // Force regeneration of the invoice
        ]);

        $this->js("window.open('$url', '_blank')");
    }

    protected function getVenueGroupInvoiceModalDescription(User $record, string $userTimezone): HtmlString
    {
        // Get the venue group
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        if (! $venueGroup) {
            return new HtmlString('<p>No venue group found for this user.</p>');
        }

        // Parse dates in user's timezone
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Check for any overlapping invoices
        $overlappingInvoices = $this->getOverlappingInvoices(null, $venueGroup->id, $startDateCarbon, $endDateCarbon);
        $warningHtml = $this->generateOverlappingInvoicesWarning($overlappingInvoices,
            'Overlapping Group Invoices Found');

        $standardHtml = <<<'HTML'
            <div class="space-y-2 text-sm text-gray-600">
                <p>This will generate an invoice for all venues in this group for the selected date range.</p>
                <p class="p-1 text-xs font-semibold text-gray-600 bg-gray-100 border border-gray-300 rounded-md">
                    This action will be logged for audit purposes.
                </p>
            </div>
        HTML;

        // Combine both HTML blocks
        $combinedHtml = $warningHtml.$standardHtml;

        return new HtmlString($combinedHtml);
    }

    protected function getOverlappingInvoices(?int $venueId, ?int $venueGroupId, Carbon $startDate, Carbon $endDate)
    {
        $query = VenueInvoice::query();

        if ($venueId) {
            $query->where('venue_id', $venueId)->whereNull('venue_group_id');
        } elseif ($venueGroupId) {
            $query->where('venue_group_id', $venueGroupId);
        }

        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                // Start date is within our range
                $q->whereDate('start_date', '>=', $startDate->format('Y-m-d'))
                    ->whereDate('start_date', '<=', $endDate->format('Y-m-d'));
            })->orWhere(function ($q) use ($startDate, $endDate) {
                // End date is within our range
                $q->whereDate('end_date', '>=', $startDate->format('Y-m-d'))
                    ->whereDate('end_date', '<=', $endDate->format('Y-m-d'));
            })->orWhere(function ($q) use ($startDate, $endDate) {
                // Invoice range completely covers our range
                $q->whereDate('start_date', '<=', $startDate->format('Y-m-d'))
                    ->whereDate('end_date', '>=', $endDate->format('Y-m-d'));
            });
        })->get();
    }

    protected function generateOverlappingInvoicesWarning(
        $overlappingInvoices,
        string $title = 'Overlapping Invoices Found'
    ): HtmlString {
        if ($overlappingInvoices->count() === 0) {
            return new HtmlString('');
        }

        $invoiceItems = [];
        foreach ($overlappingInvoices as $i => $invoice) {
            $invoiceStartDate = Carbon::createFromFormat('Y-m-d',
                substr((string) $invoice->start_date, 0, 10))->format('M d, Y');
            $invoiceEndDate = Carbon::createFromFormat('Y-m-d',
                substr((string) $invoice->end_date, 0, 10))->format('M d, Y');

            $divider = $i > 0 ? '<div class="border-t border-blue-100"></div>' : '';

            $invoiceItems[] = <<<HTML
                {$divider}
                <div class="px-3 py-2 text-left">
                    <div class="text-xs font-medium text-left text-blue-700">{$invoice->invoice_number}</div>
                    <div class="text-xs text-left text-gray-500">{$invoiceStartDate} - {$invoiceEndDate}</div>
                </div>
            HTML;
        }

        $isGroupInvoice = $overlappingInvoices->first()->venue_group_id !== null;
        $invoiceType = $isGroupInvoice ? 'group ' : '';
        $blueColor = $isGroupInvoice ? '700' : '600';

        $html = <<<HTML
            <div class="p-3 mb-4 text-left border border-blue-200 rounded-lg bg-blue-50">
                <h3 class="mb-2 text-sm font-medium text-center text-blue-600">{$title}</h3>
                <div class="overflow-hidden bg-white border border-blue-100 rounded-md">
                    {$this->implodeHtml($invoiceItems)}
                </div>
                <p class="mt-2 text-xs text-center text-blue-{$blueColor}">
                    You can still proceed to create a new {$invoiceType}invoice for this date range if needed.
                </p>
            </div>
        HTML;

        return new HtmlString($html);
    }

    /**
     * Join HTML strings without adding spaces between them
     */
    private function implodeHtml(array $htmlStrings): string
    {
        return implode('', $htmlStrings);
    }

    public function clearFilters(): void
    {
        $this->form->fill([]);
        $this->resetTable();
    }

    // ================= Visibility Helpers for Invoice Actions =================

    protected function getVenueGroupModalHeading(User $record): string
    {
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        return $venueGroup ? "Generate Group Invoice for {$venueGroup->name}" : 'Generate Group Invoice';
    }

    // ================= CSV Export Actions =================

    protected function getExportHeaderActions(string $role): array
    {
        $tz = auth()->user()->timezone ?? config('app.timezone');
        $startDate = Carbon::parse($this->data['startDate'], $tz)->format('Y-m-d');
        $endDate = Carbon::parse($this->data['endDate'], $tz)->format('Y-m-d');
        $dateRange = "{$startDate}_to_{$endDate}";

        // Base configurations
        $exportAllConfig = ExcelExport::make('table')
            ->fromTable()
            ->withWriterType(Excel::CSV)
            ->withFilename("Payment-Exports-{$role}-{$dateRange}");

        $exportMissingConfig = ExcelExport::make('table')
            ->fromTable()
            ->withWriterType(Excel::CSV)
            ->withFilename("Missing-Banking-{$role}-{$dateRange}");

        // Role-specific columns and modifications
        if ($role === 'venue') {
            $exportAllConfig->withColumns([
                Column::make('name')->heading('Venue Name'),
                Column::make('user.name')->heading('Owner Name'),
                Column::make('user.email')->heading('Owner Email'),
                Column::make('user.phone')->heading('Owner Phone'),
                Column::make('bookings_count')->heading('Bookings Count'),
                Column::make('total_earnings')
                    ->formatStateUsing(fn (Model $record) => money($record->total_earnings, $record->currency)),
                Column::make('currency')->heading('Currency'),
                // Address Info (from User)
                Column::make('user.address_1')->heading('Address 1'),
                Column::make('user.address_2')->heading('Address 2'),
                Column::make('user.city')->heading('City'),
                Column::make('user.state')->heading('State'),
                Column::make('user.zip')->heading('Zip'),
                Column::make('user.country')->heading('Country'),
                // Banking Info (from User)
                Column::make('user.payout.payout_name')->heading('Payout Name'),
                Column::make('user.payout.payout_type')->heading('Payout Type'),
                Column::make('user.payout.account_type')->heading('Account Type'),
                Column::make('user.payout.account_number')->heading('Account Number'),
                Column::make('user.payout.routing_number')->heading('Routing Number'),
            ]);

            $exportMissingConfig->modifyQueryUsing(fn ($query) => $query->whereHas('user', function ($q) {
                $q->where(fn ($qq) => $qq->whereNull('payout')->orWhere('payout', '=', '')->orWhere('payout', '=',
                    '{}'));
            }))
                ->withColumns([
                    Column::make('name')->heading('Venue Name'),
                    Column::make('user.name')->heading('Owner Name'),
                    Column::make('user.email')->heading('Owner Email'),
                    Column::make('user.phone')->heading('Owner Phone'),
                ]);

        } elseif ($role === 'venue_group') {
            $exportAllConfig->withColumns([
                Column::make('primaryManagedVenueGroups.0.name')->heading('Group Name'), // Access via relation
                Column::make('name')->heading('Manager Name'),
                Column::make('email')->heading('Manager Email'),
                Column::make('phone')->heading('Manager Phone'),
                Column::make('bookings_count')->heading('Total Bookings (Group)'),
                Column::make('total_earnings')
                    ->formatStateUsing(fn (Model $record) => money($record->total_earnings, $record->currency)),
                Column::make('currency')->heading('Currency'),
                // Address Info (from User)
                Column::make('address_1')->heading('Address 1'),
                Column::make('address_2')->heading('Address 2'),
                Column::make('city')->heading('City'),
                Column::make('state')->heading('State'),
                Column::make('zip')->heading('Zip'),
                Column::make('country')->heading('Country'),
                // Banking Info (from User)
                Column::make('payout.payout_name')->heading('Payout Name'),
                Column::make('payout.payout_type')->heading('Payout Type'),
                Column::make('payout.account_type')->heading('Account Type'),
                Column::make('payout.account_number')->heading('Account Number'),
                Column::make('payout.routing_number')->heading('Routing Number'),
            ]);

            $exportMissingConfig->modifyQueryUsing(fn ($query) => $query->where(function ($q) {
                $q->whereNull('payout')->orWhere('payout', '=', '')->orWhere('payout', '=', '{}');
            }))
                ->withColumns([
                    Column::make('primaryManagedVenueGroups.0.name')->heading('Group Name'),
                    Column::make('name')->heading('Manager Name'),
                    Column::make('email')->heading('Manager Email'),
                    Column::make('phone')->heading('Manager Phone'),
                ]);

        } else { // Concierge / Partner
            $exportAllConfig->withColumns([
                // Use original formatting closure for name
                Column::make('name')->heading('Name')->formatStateUsing(function (User $record) {
                    if ($record->hasRole('concierge')) {
                        return $record->name." - Hotel/Company: {$record->concierge?->hotel_name}";
                    } elseif ($record->hasRole('partner')) {
                        return $record->name.' - Partner';
                    }

                    return $record->name;
                }),
                Column::make('email')->heading('Email'),
                Column::make('phone')->heading('Phone'),
                Column::make('bookings_count')->heading('Bookings Count'),
                Column::make('total_earnings')
                    ->formatStateUsing(fn (Model $record) => money($record->total_earnings, $record->currency)),
                Column::make('currency')->heading('Currency'),
                // Address Info
                Column::make('address_1')->heading('Address 1'),
                Column::make('address_2')->heading('Address 2'),
                Column::make('city')->heading('City'),
                Column::make('state')->heading('State'),
                Column::make('zip')->heading('Zip'),
                Column::make('country')->heading('Country'),
                // Banking Info
                Column::make('payout.payout_name')->heading('Payout Name'),
                Column::make('payout.payout_type')->heading('Payout Type'),
                Column::make('payout.account_type')->heading('Account Type'),
                Column::make('payout.account_number')->heading('Account Number'),
                Column::make('payout.routing_number')->heading('Routing Number'),
            ]);

            $exportMissingConfig->modifyQueryUsing(fn ($query) => $query->where(function ($q) {
                $q->whereNull('payout')->orWhere('payout', '=', '')->orWhere('payout', '=', '{}');
            }))
                ->withColumns([
                    // Use original formatting closure for name
                    Column::make('name')->heading('Name')->formatStateUsing(function (User $record) {
                        if ($record->hasRole('concierge')) {
                            return $record->name." - Hotel/Company: {$record->concierge?->hotel_name}";
                        } elseif ($record->hasRole('partner')) {
                            return $record->name.' - Partner';
                        }

                        return $record->name;
                    }),
                    Column::make('email')->heading('Email'),
                    Column::make('phone')->heading('Phone'),
                ]);
        }

        return [
            ExportAction::make('exportAll')
                ->label('Export All')
                ->exports([$exportAllConfig]),
            ExportAction::make('exportMissingBankInfo')
                ->label('Export Missing Banking')
                ->exports([$exportMissingConfig]),
        ];
    }
}
