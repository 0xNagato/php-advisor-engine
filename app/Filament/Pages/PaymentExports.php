<?php

namespace App\Filament\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\UserResource;
use App\Models\Earning;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
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
                'startDate' => now()->subDays(30)->format('Y-m-d'),
                'endDate' => now()->format('Y-m-d'),
                'name_search' => '',
                'user_type' => [],
                'min_amount' => '',
                'max_amount' => '',
                'currency' => '',
            ]);
        }
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

    public function table(Table $table): Table
    {
        $query = User::query()
            ->join('earnings', 'users.id', '=', 'earnings.user_id')
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', BookingStatus::PAYOUT_STATUSES)
            ->select([
                'users.*',
                'earnings.currency',
                DB::raw('SUM(earnings.amount) as total_earnings'),
                DB::raw('COUNT(DISTINCT earnings.booking_id) as bookings_count'),
            ])
            ->when($this->data['startDate'] ?? null, function (Builder $query) {
                $query->where('bookings.confirmed_at', '>=',
                    Carbon::parse($this->data['startDate'])->startOfDay());
            })
            ->when($this->data['endDate'] ?? null, function (Builder $query) {
                $query->where('bookings.confirmed_at', '<=',
                    Carbon::parse($this->data['endDate'])->endOfDay());
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
            ->groupBy('users.id', 'earnings.currency');

        // Apply role filter
        if ($this->data['role'] ?? null) {
            $query->when($this->data['role'] === 'venue', fn (Builder $q) => $q->has('venue'))
                ->when($this->data['role'] === 'concierge', fn (Builder $q) => $q->has('concierge'))
                ->when($this->data['role'] === 'partner', fn (Builder $q) => $q->has('partner'));
        }

        $startDate = Carbon::parse($this->data['startDate'])->format('M j, y');
        $endDate = Carbon::parse($this->data['endDate'])->format('M j, y');
        $dateRange = "{$startDate}-{$endDate}";

        return $table
            ->query($query)
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
                    ->words(2)
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->size('xs')
                    ->color('primary')
                    ->sortable()
                    ->url(fn (User $record): string => BookingSearch::getUrl([
                        'filters' => [
                            'user_id' => $record->id,
                            'start_date' => $this->data['startDate'] ?? now()->subDays(30)->format('Y-m-d'),
                            'end_date' => $this->data['endDate'] ?? now()->format('Y-m-d'),
                            'status' => [BookingStatus::CONFIRMED->value],
                        ],
                    ])),
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
                                $startDate = Carbon::parse($this->data['startDate'])->startOfDay();
                                $endDate = Carbon::parse($this->data['endDate'])->endOfDay();

                                $earnings = Earning::query()
                                    ->with(['booking'])
                                    ->where('user_id', $record->id)
                                    ->whereHas('booking', function (Builder $query) use ($startDate, $endDate) {
                                        $query->whereBetween('confirmed_at', [$startDate, $endDate]);
                                    })
                                    ->get()
                                    ->groupBy('booking_id');

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
            ->headerActions([
                ExportAction::make('exportAll')
                    ->size('xs')
                    ->exports([
                        ExcelExport::make('table')
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
                            ->withFilename("Earnings-{$dateRange}"),
                    ]),
                ExportAction::make('exportMissingBankInfo')
                    ->label('Export Missing Bank Info')
                    ->size('xs')
                    ->exports([
                        ExcelExport::make('missing_bank_info')
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
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
