<?php

namespace App\Filament\Pages;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class BookingSearch extends Page implements HasTable
{
    use InteractsWithTable;

    #[Url()]
    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Booking Search';

    protected static ?string $title = 'Booking Search';

    protected static ?string $navigationGroup = 'Advanced Tools';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.booking-search';

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public function mount(): void
    {
        if (blank($this->data)) {
            $this->form->fill([
                'booking_id' => '',
                'customer_search' => '',
                'venue_search' => '',
                'concierge_search' => '',
                'status' => [
                ],
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->live()
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->live()
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('booking_id')
                                    ->label('Booking ID')
                                    ->placeholder('ID')
                                    ->numeric()
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                                TextInput::make('customer_search')
                                    ->label('Customer Search')
                                    ->placeholder('Name, Email or Phone')
                                    ->live(debounce: 500)
                                    ->minLength(3)
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                                TextInput::make('user_id')
                                    ->label('User ID')
                                    ->numeric()
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('venue_search')
                                    ->label('Venue Search')
                                    ->placeholder('Venue Name')
                                    ->live(debounce: 500)
                                    ->minLength(3)
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                                TextInput::make('concierge_search')
                                    ->label('Concierge Search')
                                    ->placeholder('Concierge Name')
                                    ->live(debounce: 500)
                                    ->minLength(3)
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                                Select::make('status')
                                    ->options([
                                        BookingStatus::PENDING->value => BookingStatus::PENDING->label(),
                                        BookingStatus::GUEST_ON_PAGE->value => BookingStatus::GUEST_ON_PAGE->label(),
                                        BookingStatus::ABANDONED->value => BookingStatus::ABANDONED->label(),
                                        BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
                                        BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
                                        BookingStatus::REFUNDED->value => BookingStatus::REFUNDED->label(),
                                        BookingStatus::PARTIALLY_REFUNDED->value => BookingStatus::PARTIALLY_REFUNDED->label(),
                                    ])
                                    ->placeholder('All Statuses')
                                    ->live()
                                    ->afterStateUpdated(function () {
                                        $this->resetTable();
                                    }),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        $query = Booking::query()
            ->with(['venue', 'concierge.user', 'schedule.venue']);

        // Apply filters
        if ($this->data['booking_id'] ?? null) {
            $query->where('id', $this->data['booking_id']);
        }

        if ($this->data['user_id'] ?? null) {
            $query->where(function ($query) {
                $query->whereHas('venue.user', fn (Builder $q) => $q->where('id', $this->data['user_id']))
                    ->orWhereHas('concierge.user', fn (Builder $q) => $q->where('id', $this->data['user_id']))
                    ->orWhereHas('partnerVenue.user', fn (Builder $q) => $q->where('id', $this->data['user_id']))
                    ->orWhereHas('partnerConcierge.user', fn (Builder $q) => $q->where('id', $this->data['user_id']));
            });
        }

        if ($this->data['start_date'] ?? null) {
            $query->where('booking_at', '>=',
                Carbon::parse($this->data['start_date'])->startOfDay());
        }

        if ($this->data['end_date'] ?? null) {
            $query->where('booking_at', '<=',
                Carbon::parse($this->data['end_date'])->endOfDay());
        }

        if ($this->data['customer_search'] ?? null) {
            $search = $this->data['customer_search'];
            $terms = explode(' ', (string) $search);

            $query->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('guest_first_name', 'like', "%{$term}%")
                        ->orWhere('guest_last_name', 'like', "%{$term}%")
                        ->orWhere('guest_email', 'like', "%{$term}%")
                        ->orWhere('guest_phone', 'like', "%{$term}%");
                }
            });
        }

        if ($this->data['venue_search'] ?? null) {
            $search = $this->data['venue_search'];
            $query->whereHas('venue', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($this->data['concierge_search'] ?? null) {
            $search = $this->data['concierge_search'];
            $terms = explode(' ', (string) $search);

            $query->whereHas('concierge.user', function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($q) use ($term) {
                        $q->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    });
                }
            });
        }

        if ($this->data['status'] ?? null) {
            $statuses = is_array($this->data['status']) ? $this->data['status'] : [$this->data['status']];
            $query->whereIn('status', $statuses);
        }

        $startDate = $this->data['start_date'] ? Carbon::parse($this->data['start_date'])->format('M j, Y') : 'All Time';
        $endDate = $this->data['end_date'] ? Carbon::parse($this->data['end_date'])->format('M j, Y') : 'Present';
        $dateRange = "{$startDate}-{$endDate}";

        return $table
            ->query($query)
            ->heading('Bookings: '.$startDate.' to '.$endDate)
            ->recordUrl(fn (Booking $record) => route('filament.admin.resources.bookings.view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Created')
                    ->size('xs')
                    ->formatStateUsing(fn (Booking $record): string => Carbon::parse($record->created_at)
                        ->timezone(auth()->user()->timezone ?? config('app.timezone'))
                        ->format('M j, Y g:ia'))
                    ->sortable(),
                TextColumn::make('booking_at')
                    ->label('Booking Date')
                    ->size('xs')
                    ->formatStateUsing(fn (Booking $record): string => $record->booking_at->format('M j, Y g:ia'))
                    ->sortable(),
                TextColumn::make('guest_name')
                    ->label('Guest Information')
                    ->size('xs')
                    ->formatStateUsing(function (Booking $record): string {
                        $parts = [];

                        if ($record->guest_name) {
                            $parts[] = $record->guest_name;
                        }
                        if ($record->guest_phone) {
                            $parts[] = $record->guest_phone;
                        }
                        if ($record->guest_email) {
                            $parts[] = $record->guest_email;
                        }

                        return implode('<br>', $parts);
                    })
                    ->html(),
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->size('xs')
                    ->sortable(),
                TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->size('xs'),
                TextColumn::make('status')
                    ->badge()
                    ->size('xs')
                    ->color(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::CONFIRMED => 'success',
                        BookingStatus::PENDING => 'warning',
                        BookingStatus::GUEST_ON_PAGE => 'warning',
                        BookingStatus::CANCELLED => 'danger',
                        BookingStatus::NO_SHOW => 'gray',
                        BookingStatus::REFUNDED => 'danger',
                        BookingStatus::PARTIALLY_REFUNDED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_fee')
                    ->money(fn ($record) => $record->currency, 100)
                    ->size('xs')
                    ->sortable(),
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->hidden(),
                TextColumn::make('guest_email')
                    ->label('Guest Email')
                    ->hidden(),
                TextColumn::make('guest_phone')
                    ->label('Guest Phone')
                    ->hidden(),
                TextColumn::make('guest_count')
                    ->label('Guest Count')
                    ->hidden(),
                TextColumn::make('total_fee')
                    ->label('Total Fee')
                    ->formatStateUsing(fn ($record) => money($record->total_fee, $record->currency))
                    ->hidden(),
                TextColumn::make('currency')
                    ->hidden(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label())
                    ->hidden(),
            ])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Results')
                    ->size('xs')
                    ->exports([
                        ExcelExport::make('bookings')
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
                            ->withFilename("Bookings-Export-{$dateRange}"),
                    ]),
            ])
            ->paginated([25, 50, 100, 250]);
    }

    public function clearFilters(): void
    {
        $this->form->fill([]);
        $this->resetTable();
    }
}
