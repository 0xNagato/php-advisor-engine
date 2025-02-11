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
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class BookingSearch extends Page implements HasTable
{
    use InteractsWithTable;

    #[Url(as: 'filters')]
    public ?array $data = [
        'booking_id' => '',
        'customer_search' => '',
        'venue_search' => '',
        'concierge_search' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => [],
        'user_id' => '',
        'show_booking_time' => true,
    ];

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
        $timezone = auth()->user()?->timezone ?? config('app.default_timezone');

        if (blank($this->data)) {
            $this->form->fill([
                'booking_id' => '',
                'customer_search' => '',
                'venue_search' => '',
                'concierge_search' => '',
                'start_date' => now($timezone)->subDays(30)->format('Y-m-d'),
                'end_date' => now($timezone)->format('Y-m-d'),
                'status' => [],
                'user_id' => '',
                'show_booking_time' => true,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(3)
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
                                Select::make('show_booking_time')
                                    ->label('Date Type')
                                    ->options([
                                        true => 'Booking Time',
                                        false => 'Creation Time',
                                    ])
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
                                        BookingStatus::NO_SHOW->value => BookingStatus::NO_SHOW->label(),
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
        $userTimezone = auth()->user()?->timezone ?? config('app.default_timezone');

        return $table
            ->query(function (Builder $query) use ($userTimezone) {
                $dateColumn = $this->data['show_booking_time'] ? 'bookings.booking_at' : 'bookings.created_at';

                $startDate = Carbon::parse($this->data['start_date'], $userTimezone)
                    ->startOfDay()
                    ->setTimezone('UTC');

                $endDate = Carbon::parse($this->data['end_date'], $userTimezone)
                    ->endOfDay()
                    ->setTimezone('UTC');

                $query = Booking::query()
                    ->when(
                        filled($this->data['start_date']) && filled($this->data['end_date']),
                        fn ($query) => $query->whereBetween($dateColumn, [$startDate, $endDate])
                    );

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

                return $query;
            })
            ->heading('Bookings: '.($this->data['start_date']
                ? Carbon::parse($this->data['start_date'])->format('M j, Y')
                : 'All Time').' to '.($this->data['end_date']
                ? Carbon::parse($this->data['end_date'])->format('M j, Y')
                : 'Present'))
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Results')
                    ->size('xs')
                    ->exports([
                        ExcelExport::make('bookings')
                            ->fromTable()
                            ->except(['no_show'])
                            ->withWriterType(Excel::CSV)
                            ->withFilename('Bookings-Export-'.($this->data['start_date'] ? Carbon::parse($this->data['start_date'])->format('M j, Y').'-'.Carbon::parse($this->data['end_date'])->format('M j, Y') : 'All Time to Present')),
                    ]),
            ])
            ->recordUrl(fn (Booking $record) => route('filament.admin.resources.bookings.view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->hidden(),
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
                    ->formatStateUsing(fn (Booking $record): string => Carbon::parse($record->booking_at)
                        ->timezone(auth()->user()?->timezone ?? config('app.default_timezone'))
                        ->format('M j, Y g:ia'))
                    ->sortable(),
                TextColumn::make('is_prime')
                    ->label('Prime')
                    ->alignCenter()
                    ->formatStateUsing(fn (Booking $record): string => $record->is_prime ? 'Yes' : 'No')
                    ->size('xs'),
                TextColumn::make('no_show')
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
                TextColumn::make('guest_name')
                    ->hidden(),
                TextColumn::make('guest_email')
                    ->hidden(),
                TextColumn::make('guest_phone')
                    ->hidden(),
                TextColumn::make('guest_count')
                    ->hidden(),
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->size('xs')
                    ->sortable(),
                TextColumn::make('venue.region')
                    ->hidden(),
                TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->size('xs')
                    ->color('primary')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->action(
                        Action::make('viewConcierge')
                            ->modalHeading(fn (Booking $record): string => $record->concierge?->user?->name ?? 'No Concierge')
                            ->modalContent(fn (Booking $record): HtmlString => new HtmlString(
                                "<div class='space-y-4'>".
                                "<div class='grid grid-cols-2 gap-4 text-sm'>".
                                    '<div>'.
                                        "<span class='block font-medium text-gray-500'>Hotel/Company</span>".
                                        "<span class='block'>{$record->concierge?->hotel_name}</span>".
                                    '</div>'.
                                    '<div>'.
                                        "<span class='block font-medium text-gray-500'>Phone</span>".
                                        "<span class='block'>{$record->concierge?->user?->phone}</span>".
                                    '</div>'.
                                    '<div>'.
                                        "<span class='block font-medium text-gray-500'>Email</span>".
                                        "<span class='block'>{$record->concierge?->user?->email}</span>".
                                    '</div>'.
                                '</div>'.
                                '</div>'
                            ))
                            ->modalActions([
                                Action::make('edit')
                                    ->label('Edit')
                                    ->url(fn (Booking $record) => route('filament.admin.resources.users.edit', ['record' => $record->concierge?->user_id]))
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-m-pencil-square')
                                    ->color('warning'),
                                Action::make('overview')
                                    ->label('Overview')
                                    ->url(fn (Booking $record) => route('filament.admin.resources.concierges.view', ['record' => $record->concierge?->id]))
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-m-document-text')
                                    ->color('info'),
                                Action::make('bookings')
                                    ->label('Bookings')
                                    ->url(fn (Booking $record) => route('filament.admin.pages.booking-search', ['filters' => ['concierge_search' => $record->concierge?->user?->name]]))
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-m-calendar')
                                    ->color('success'),
                            ])
                            ->modalWidth('md')
                    ),
                TextColumn::make('concierge.hotel_name')
                    ->label('Hotel/Company')
                    ->hidden(),
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
            ])
            ->paginated([25, 50, 100, 250]);
    }

    public function clearFilters(): void
    {
        $this->form->fill([]);
        $this->resetTable();
    }
}
