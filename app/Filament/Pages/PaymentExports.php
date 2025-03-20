<?php

namespace App\Filament\Pages;

use App\Enums\BookingStatus;
use App\Enums\EarningType;
use App\Filament\Resources\UserResource;
use App\Models\Earning;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
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
                'role' => '',
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
                            ])
                            ->placeholder('All Roles')
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
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

        return [
            // Individual venue invoice actions
            Action::make('downloadInvoice')
                ->icon('heroicon-m-arrow-down-on-square')
                ->color('primary')
                ->iconButton()
                ->visible(fn (User $record) => $this->canDownloadVenueInvoice($record, $userTimezone))
                ->tooltip('Download Invoice')
                ->action(fn (User $record) => $this->downloadVenueInvoice($record)),

            Action::make('generateInvoice')
                ->icon('heroicon-m-document-plus')
                ->color('info')
                ->iconButton()
                ->visible(fn (User $record) => $this->canGenerateVenueInvoice($record, $userTimezone))
                ->requiresConfirmation()
                ->modalHeading(fn (User $record) => $record->venue ? "Generate Invoice for {$record->venue->name}" : 'Generate Invoice')
                ->modalDescription(fn (User $record) => $this->getVenueInvoiceModalDescription($record, $userTimezone))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Invoice Generated')
                        ->body('The invoice has been generated and will open in a new tab.')
                )
                ->tooltip('Generate Invoice')
                ->action(fn (User $record) => $this->generateVenueInvoice($record)),

            // Venue group invoice actions
            Action::make('downloadGroupInvoice')
                ->icon('heroicon-m-arrow-down-on-square')
                ->color('success')
                ->iconButton()
                ->visible(fn (User $record) => $this->canDownloadVenueGroupInvoice($record, $userTimezone))
                ->tooltip('Download Group Invoice')
                ->action(fn (User $record) => $this->downloadVenueGroupInvoice($record)),

            Action::make('generateGroupInvoice')
                ->icon('heroicon-m-document-plus')
                ->color('success')
                ->iconButton()
                ->visible(fn (User $record) => $this->canGenerateVenueGroupInvoice($record, $userTimezone))
                ->requiresConfirmation()
                ->modalHeading(fn (User $record) => $this->getVenueGroupModalHeading($record))
                ->modalDescription(fn (User $record) => $this->getVenueGroupInvoiceModalDescription($record, $userTimezone))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Group Invoice Generated')
                        ->body('The group invoice has been generated and will open in a new tab.')
                )
                ->tooltip('Generate Group Invoice')
                ->action(fn (User $record) => $this->generateVenueGroupInvoice($record)),
        ];
    }

    protected function canDownloadVenueInvoice(User $record, string $userTimezone): bool
    {
        // Show only for venues with existing invoices for exact date range
        if (! $record->hasRole('venue') || ! $record->venue) {
            return false;
        }

        // Don't show for venues that are part of a venue group
        if ($record->venue->venueGroup) {
            return false;
        }

        // Parse dates in user's timezone
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Check if an invoice with the exact date range exists
        return VenueInvoice::query()
            ->where('venue_id', $record->venue->id)
            ->whereNull('venue_group_id')
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->exists();
    }

    protected function canGenerateVenueInvoice(User $record, string $userTimezone): bool
    {
        if (! $record->hasRole('venue') || ! $record->venue) {
            return false;
        }

        // Don't show for venues that are part of a venue group
        if ($record->venue->venueGroup) {
            return false;
        }

        // Parse dates in user's timezone
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Only hide the generate button if an invoice with the exact date range exists
        return ! VenueInvoice::query()
            ->where('venue_id', $record->venue->id)
            ->whereNull('venue_group_id')
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->exists();
    }

    protected function downloadVenueInvoice(User $record): void
    {
        // Ensure venue exists
        if (! $record->venue) {
            return;
        }

        // Just pass the dates directly to the route
        $url = route('venue.invoice.download', [
            'user' => $record->id,
            'startDate' => $this->data['startDate'],
            'endDate' => $this->data['endDate'],
        ]);

        $this->js("window.open('$url', '_blank')");
    }

    protected function generateVenueInvoice(User $record): void
    {
        // Ensure venue exists
        if (! $record->venue) {
            return;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($record->venue)
            ->withProperties([
                'date_range' => [
                    'start' => $this->data['startDate'],
                    'end' => $this->data['endDate'],
                ],
                'generated_by' => auth()->user()->name,
                'generated_at' => now(),
            ])
            ->log('Generated venue invoice');

        $url = route('venue.invoice.download', [
            'user' => $record->id,
            'startDate' => $this->data['startDate'],
            'endDate' => $this->data['endDate'],
        ]);

        $this->js("window.open('$url', '_blank')");
    }

    protected function getVenueInvoiceModalDescription(User $record, string $userTimezone): HtmlString
    {
        // Check if venue exists
        if (! $record->venue) {
            return new HtmlString('<p>No venue found for this user.</p>');
        }

        // Parse dates in user's timezone
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Check for any overlapping invoices
        $overlappingInvoices = $this->getOverlappingInvoices($record->venue->id, null, $startDateCarbon, $endDateCarbon);
        $warningHtml = $this->generateOverlappingInvoicesWarning($overlappingInvoices, 'Overlapping Invoices Found');

        $standardHtml = <<<'HTML'
            <div class="space-y-2 text-sm text-gray-600">
                <p>This will generate an invoice for all bookings in the selected date range. The process may take a few moments to complete as we generate and upload the PDF.</p>
                <p class="p-1 text-xs font-semibold text-gray-600 bg-gray-100 border border-gray-300 rounded-md">
                    This action will be logged for audit purposes.
                </p>
            </div>
        HTML;

        // Combine both HTML blocks
        $combinedHtml = $warningHtml.$standardHtml;

        return new HtmlString($combinedHtml);
    }

    protected function canDownloadVenueGroupInvoice(User $record, string $userTimezone): bool
    {
        // Show only for venue group primary managers with existing invoices
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        if (! $venueGroup) {
            return false;
        }

        // Parse dates in user's timezone
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Check if an invoice with the exact date range exists
        return VenueInvoice::query()
            ->where('venue_group_id', $venueGroup->id)
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->exists();
    }

    protected function canGenerateVenueGroupInvoice(User $record, string $userTimezone): bool
    {
        // Only show for venue group primary managers without existing invoices for exact date range
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        if (! $venueGroup) {
            return false;
        }

        // Parse dates in user's timezone
        $startDateCarbon = Carbon::parse($this->data['startDate'], $userTimezone);
        $endDateCarbon = Carbon::parse($this->data['endDate'], $userTimezone);

        // Only hide the generate button if an invoice with the exact date range exists
        return ! VenueInvoice::query()
            ->where('venue_group_id', $venueGroup->id)
            ->whereDate('start_date', $startDateCarbon->format('Y-m-d'))
            ->whereDate('end_date', $endDateCarbon->format('Y-m-d'))
            ->exists();
    }

    protected function getVenueGroupModalHeading(User $record): string
    {
        $venueGroup = VenueGroup::query()->where('primary_manager_id', $record->id)->first();

        return $venueGroup ? "Generate Group Invoice for {$venueGroup->name}" : 'Generate Group Invoice';
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
        $warningHtml = $this->generateOverlappingInvoicesWarning($overlappingInvoices, 'Overlapping Group Invoices Found');

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

    protected function generateOverlappingInvoicesWarning($overlappingInvoices, string $title = 'Overlapping Invoices Found'): HtmlString
    {
        if ($overlappingInvoices->count() === 0) {
            return new HtmlString('');
        }

        $invoiceItems = [];
        foreach ($overlappingInvoices as $i => $invoice) {
            $invoiceStartDate = Carbon::createFromFormat('Y-m-d', substr((string) $invoice->start_date, 0, 10))->format('M d, Y');
            $invoiceEndDate = Carbon::createFromFormat('Y-m-d', substr((string) $invoice->end_date, 0, 10))->format('M d, Y');

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

    public function table(Table $table): Table
    {
        // Get the authenticated user's timezone
        $userTimezone = auth()->user()->timezone ?? config('app.timezone');

        // QUERY 1: Individual users (venues without group, concierges, partners)
        $individualUsersQuery = User::query()
            ->leftJoin('venues', function ($join) {
                $join->on('users.id', '=', 'venues.user_id')
                    ->whereNull('venues.venue_group_id'); // Only include venues NOT in groups
            })
            ->join('earnings', 'users.id', '=', 'earnings.user_id')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', BookingStatus::PAYOUT_STATUSES)
            // Only include venue-related earning types for venue users
            ->when($this->data['role'] === 'venue', function ($query) {
                $query->whereIn('earnings.type', [
                    EarningType::VENUE->value,
                    EarningType::VENUE_PAID->value,
                ]);
            })
            ->select([
                'users.*',
                'earnings.currency',
                DB::raw('SUM(earnings.amount) as total_earnings'),
                DB::raw('COUNT(DISTINCT earnings.booking_id) as bookings_count'),
                DB::raw('NULL as venue_group_id'), // Add this to make union work properly
            ])
            ->when($this->data['startDate'] ?? null, function (Builder $query) use ($userTimezone) {
                // Parse the date in user's timezone then convert to UTC for database comparison
                $startDate = Carbon::parse($this->data['startDate'], $userTimezone)->startOfDay()->setTimezone('UTC');
                $query->where('bookings.booking_at_utc', '>=', $startDate);
            })
            ->when($this->data['endDate'] ?? null, function (Builder $query) use ($userTimezone) {
                // Parse the date in user's timezone then convert to UTC for database comparison
                $endDate = Carbon::parse($this->data['endDate'], $userTimezone)->endOfDay()->setTimezone('UTC');
                $query->where('bookings.booking_at_utc', '<=', $endDate);
            })
            ->when($this->data['name_search'] ?? null, function (Builder $query) {
                $search = $this->data['name_search'];
                $terms = explode(' ', $search);

                return $query->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->where(function ($q) use ($term) {
                            $q->where('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                        });
                    }
                });
            })
            // KEY FILTER: Make sure we ONLY include venues WITHOUT venue_group_id
            ->where(function ($query) {
                $query->whereDoesntHave('venue', function ($q) {
                    $q->whereNotNull('venue_group_id');
                })
                // Also exclude venue group primary managers
                    ->whereDoesntHave('primaryManagedVenueGroups');
            })
            ->groupBy('users.id', 'earnings.currency');

        // Apply role filter to individual users
        if ($this->data['role'] ?? null) {
            $individualUsersQuery->when($this->data['role'] === 'venue', fn (Builder $q) => $q->has('venue'))
                ->when($this->data['role'] === 'concierge', fn (Builder $q) => $q->has('concierge'))
                ->when($this->data['role'] === 'partner', fn (Builder $q) => $q->has('partner'));
        }

        // QUERY 2: Venue group primary managers with aggregated earnings from venues in the group
        $groupManagersQuery = User::query()
            ->join('venue_groups', 'users.id', '=', 'venue_groups.primary_manager_id')
            ->leftJoin(DB::raw('(
                SELECT
                    v.venue_group_id,
                    SUM(
                        CASE
                            WHEN b.is_prime = 1 AND e.type = "'.EarningType::VENUE->value.'" THEN e.amount
                            WHEN b.is_prime = 0 AND e.type = "'.EarningType::VENUE_PAID->value.'" THEN -ABS(e.amount)
                            ELSE 0
                        END
                    ) as total_earnings,
                    COUNT(DISTINCT e.booking_id) as bookings_count,
                    e.currency
                FROM venues v
                JOIN schedule_templates st ON st.venue_id = v.id
                JOIN bookings b ON b.schedule_template_id = st.id
                JOIN earnings e ON e.booking_id = b.id AND e.user_id = (
                    SELECT vg.primary_manager_id FROM venue_groups vg WHERE vg.id = v.venue_group_id
                )
                WHERE b.status IN ("'.implode('","', array_map(fn ($status) => $status->value, BookingStatus::PAYOUT_STATUSES)).'")
                AND e.type IN ("'.implode('","', [
                EarningType::VENUE->value,
                EarningType::VENUE_PAID->value,
                EarningType::REFUND->value,
            ]).'")
                '.($this->data['startDate'] ?? null ? 'AND b.booking_at_utc >= "'.Carbon::parse($this->data['startDate'], $userTimezone)->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s').'"' : '').'
                '.($this->data['endDate'] ?? null ? 'AND b.booking_at_utc <= "'.Carbon::parse($this->data['endDate'], $userTimezone)->endOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s').'"' : '').'
                GROUP BY v.venue_group_id, e.currency
            ) as venue_earnings'), 'venue_groups.id', '=', 'venue_earnings.venue_group_id')
            ->select([
                'users.*',
                'venue_earnings.currency',
                'venue_earnings.total_earnings',
                'venue_earnings.bookings_count',
                'venue_groups.id as venue_group_id',
            ])
            ->whereNotNull('venue_earnings.bookings_count')
            ->where(function ($query) {
                $query->whereNotNull('venue_earnings.total_earnings')
                    ->orWhere('venue_earnings.total_earnings', '!=', 0);
            })
            ->when($this->data['name_search'] ?? null, function (Builder $query) {
                $search = $this->data['name_search'];
                $terms = explode(' ', $search);

                return $query->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->where(function ($q) use ($term) {
                            $q->where('users.first_name', 'like', "%{$term}%")
                                ->orWhere('users.last_name', 'like', "%{$term}%");
                        });
                    }
                });
            })
            // Make sure we only include venue groups that have at least one venue
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('venues')
                    ->whereColumn('venues.venue_group_id', 'venue_groups.id');
            });

        // Apply role filter for venue groups
        if (($this->data['role'] ?? null) === 'venue') {
            $groupManagersQuery->has('primaryManagedVenueGroups');
        }

        // Combine the two queries
        $query = $individualUsersQuery->union($groupManagersQuery);

        // Format dates in user's timezone for display purposes
        $startDate = Carbon::parse($this->data['startDate'], $userTimezone)->format('M j, y');
        $endDate = Carbon::parse($this->data['endDate'], $userTimezone)->format('M j, y');
        $dateRange = "{$startDate}-{$endDate}";

        return $table
            ->query(
                User::query()
                    ->fromSub($query, 'users')
                    ->with([
                        'concierge',
                        'venue',
                        'venue.venueGroup',
                        'primaryManagedVenueGroups',
                        'primaryManagedVenueGroups.venues',
                        'partner',
                    ])
            )
            ->heading('Earnings: '.$startDate.' - '.$endDate)
            ->defaultSort('total_earnings', 'desc')
            ->paginated([10, 25, 50, 100])
            ->columns([
                // Visible Columns
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable(['first_name', 'last_name'])
                    ->color('primary')
                    ->size('xs')
                    ->wrap()
                    ->html()
                    ->formatStateUsing(function (User $record): string {
                        // For venue group primary managers, display only the venue group name and its venues
                        if ($record->primaryManagedVenueGroups->isNotEmpty()) {
                            $venueGroup = $record->primaryManagedVenueGroups->first();
                            $venuesCollection = $venueGroup->venues;
                            $venueCount = $venuesCollection->count();

                            $name = $venueGroup->name;

                            if ($venueCount <= 3) {
                                // Show all venues if there are 3 or fewer
                                $venues = $venuesCollection->pluck('name')->implode(', ');
                                $subtext = $venues;
                            } else {
                                // Show first 3 venues + count if there are more than 3
                                $shownVenues = $venuesCollection->take(3)->pluck('name')->implode(', ');
                                $remainingCount = $venueCount - 3;
                                $subtext = "{$shownVenues} +{$remainingCount} more";
                            }

                            return new HtmlString(
                                "<div class='flex flex-col gap-1'>
                                    <span>{$name}</span>
                                    ".($subtext ? "<span class='text-[10px] sm:text-xs text-gray-500'>{$subtext}</span>" : '').'
                                </div>'
                            );
                        }

                        // For normal users/venues
                        if ($record->hasRole('concierge')) {
                            $subtext = "Hotel/Company: {$record->concierge?->hotel_name}";

                            return new HtmlString(
                                "<div class='flex flex-col gap-1'>
                                    <span>{$record->name}</span>
                                    <span class='text-[10px] sm:text-xs text-gray-500'>{$subtext}</span>
                                </div>"
                            );
                        } elseif ($record->hasRole('venue') || $record->hasRole('venue_manager')) {
                            // Skip if no venue relationship loaded
                            if (! $record->venue) {
                                return new HtmlString(
                                    "<div class='flex flex-col gap-1'>
                                        <span>Unknown Venue</span>
                                        <span class='text-[10px] sm:text-xs text-gray-500'>{$record->name}</span>
                                    </div>"
                                );
                            }

                            // For venues, flip the display: venue name on top, owner name underneath
                            $venueName = $record->venue->name;

                            return new HtmlString(
                                "<div class='flex flex-col gap-1'>
                                    <span>{$venueName}</span>
                                    <span class='text-[10px] sm:text-xs text-gray-500'>{$record->name}</span>
                                </div>"
                            );
                        } elseif ($record->hasRole('partner')) {
                            $subtext = $record->partner ? 'Partner' : '';

                            return new HtmlString(
                                "<div class='flex flex-col gap-1'>
                                    <span>{$record->name}</span>
                                    ".($subtext ? "<span class='text-[10px] sm:text-xs text-gray-500'>{$subtext}</span>" : '').'
                                </div>'
                            );
                        }

                        // For any other user type
                        return new HtmlString(
                            "<div class='flex flex-col gap-1'>
                                <span>{$record->name}</span>
                            </div>"
                        );
                    })
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->size('xs')
                    ->color('primary')
                    ->sortable()
                    ->url(function (User $record): string {
                        $filters = [
                            'start_date' => $this->data['startDate'] ?? now()->subDays(30)->format('Y-m-d'),
                            'end_date' => $this->data['endDate'] ?? now()->format('Y-m-d'),
                            'status' => [BookingStatus::CONFIRMED->value],
                        ];

                        // Check if this is a venue group primary manager
                        if ($record->primaryManagedVenueGroups->isNotEmpty()) {
                            $venueGroup = $record->primaryManagedVenueGroups->first();
                            $venueIds = $venueGroup->venues->pluck('id')->toArray();
                            $filters['venue_ids'] = $venueIds;
                        } else {
                            $filters['user_id'] = $record->id;
                        }

                        return self::getUrl(['filters' => $filters]);
                    }),
                TextColumn::make('total_earnings')
                    ->label('Earnings')
                    ->size('xs')
                    ->sortable()
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => money($record->total_earnings, $record->currency).' '.$record->currency)
                    ->action(
                        Action::make('viewEarnings')
                            ->slideOver()
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalHeading(fn ($record) => "{$record->name} - Earnings Breakdown")
                            ->modalContent(function ($record) {
                                // Get the authenticated user's timezone
                                $userTimezone = auth()->user()->timezone ?? config('app.timezone');

                                // Parse dates in user's timezone then convert to UTC for database comparison
                                $startDate = Carbon::parse($this->data['startDate'], $userTimezone)->startOfDay()->setTimezone('UTC');
                                $endDate = Carbon::parse($this->data['endDate'], $userTimezone)->endOfDay()->setTimezone('UTC');

                                if ($record->primaryManagedVenueGroups->isNotEmpty()) {
                                    // For venue group managers, get earnings from all venues in the group
                                    $venueGroup = $record->primaryManagedVenueGroups->first();
                                    $venueIds = $venueGroup->venues->pluck('id')->toArray();

                                    // First get all the user IDs associated with venues in this group
                                    $venueUserIds = Venue::query()->whereIn('id', $venueIds)
                                        ->get()
                                        ->pluck('user_id')
                                        ->toArray();

                                    // Then get their earnings
                                    $earnings = Earning::query()
                                        ->with(['booking'])
                                        ->whereIn('user_id', $venueUserIds)
                                        ->whereHas('booking', function (Builder $query) use ($startDate, $endDate) {
                                            $query->whereBetween('booking_at_utc', [$startDate, $endDate]);
                                        })
                                        ->get()
                                        ->groupBy('booking_id');
                                } else {
                                    // For regular users, just get their earnings
                                    $earnings = Earning::query()
                                        ->with(['booking'])
                                        ->where('user_id', $record->id)
                                        ->whereHas('booking', function (Builder $query) use ($startDate, $endDate) {
                                            $query->whereBetween('booking_at_utc', [$startDate, $endDate]);
                                        })
                                        ->get()
                                        ->groupBy('booking_id');
                                }

                                return view('components.tables.earnings-breakdown', [
                                    'earnings' => $earnings,
                                    'currency' => $record->currency,
                                ]);
                            })
                    ),

                // Hidden Columns (for export)
                TextColumn::make('currency')->hidden(),
                TextColumn::make('email')->hidden(),
                TextColumn::make('phone')->hidden(),
                // Address Info
                TextColumn::make('address_1')->hidden(),
                TextColumn::make('address_2')->hidden(),
                TextColumn::make('city')->hidden(),
                TextColumn::make('state')->hidden(),
                TextColumn::make('zip')->hidden(),
                TextColumn::make('country')->hidden(),
                TextColumn::make('region')->hidden(),
                // Banking Info
                TextColumn::make('payout.payout_name')
                    ->label('Payout Name')
                    ->hidden(),
                TextColumn::make('payout.payout_type')
                    ->label('Payout Type')
                    ->hidden(),
                TextColumn::make('payout.account_type')
                    ->label('Account Type')
                    ->hidden(),
                TextColumn::make('payout.account_number')
                    ->label('Account Number')
                    ->hidden(),
                TextColumn::make('payout.routing_number')
                    ->label('Routing Number')
                    ->hidden(),
            ])
            ->actions($this->getInvoiceActions())
            ->headerActions([
                ExportAction::make('exportAll')
                    ->size('xs')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
                            ->withColumns([
                                Column::make('name')
                                    ->formatStateUsing(fn ($record) => $record->name.($record->hasRole('concierge')
                                            ? " - Hotel/Company: {$record->concierge?->hotel_name}"
                                            : ($record->hasRole('venue') || $record->hasRole('venue_manager')
                                                ? " - Venue: {$record->venue?->name}"
                                                : ''
                                            )
                                    )
                                    ),
                            ])
                            ->withFilename("Payment-Exports-{$dateRange}"),
                    ]),
                ExportAction::make('exportMissingBankInfo')
                    ->label('Export Missing Bank Info')
                    ->size('xs')
                    ->exports([
                        ExcelExport::make('missing_bank_info')
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
                            ->withColumns([
                                Column::make('name')
                                    ->formatStateUsing(fn ($record) => $record->name.($record->hasRole('concierge')
                                            ? " - Hotel/Company: {$record->concierge?->hotel_name}"
                                            : ($record->hasRole('venue') || $record->hasRole('venue_manager')
                                                ? " - Venue: {$record->venue?->name}"
                                                : ''
                                            )
                                    )
                                    ),
                            ])
                            ->modifyQueryUsing(fn ($query) => $query->where(function ($q) {
                                $q->whereNull('payout')
                                    ->orWhere('payout', '=', '')
                                    ->orWhere('payout', '=', '{}');
                            }))
                            ->except([
                                'first_name',
                                'last_name',
                                'address_1',
                                'address_2',
                                'city',
                                'state',
                                'zip',
                                'country',
                                'payout.payout_name',
                                'payout.payout_type',
                                'payout.account_type',
                                'payout.account_number',
                                'payout.routing_number',
                            ])
                            ->withFilename("Missing-Banking-Info-{$dateRange}"),
                    ]),
            ]);
    }

    public function clearFilters(): void
    {
        $this->form->fill([]);
        $this->resetTable();
    }
}
