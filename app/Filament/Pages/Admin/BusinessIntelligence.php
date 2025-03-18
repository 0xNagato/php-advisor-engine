<?php

namespace App\Filament\Pages\Admin;

use App\Filament\DateRangeFilterAction;
use App\Livewire\DateRangeFilterWidget;
use App\Models\Concierge;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class BusinessIntelligence extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $title = 'Business Intelligence';

    protected static ?int $navigationSort = 45;

    protected static string $view = 'filament.pages.admin.business-intelligence';

    public bool $isLoading = false;

    public ?array $filters = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin');
    }

    public function mount(): void
    {
        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $this->filters['startDate'] ??= now($timezone)->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now($timezone)->format('Y-m-d');
    }

    public function getHeading(): string|Htmlable
    {
        return 'Business Intelligence';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null;
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $formattedStartDate = $startDate->format('M j');
        $formattedEndDate = $endDate->format('M j');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DateRangeFilterWidget::make([
                'startDate' => $this->filters['startDate'],
                'endDate' => $this->filters['endDate'],
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DateRangeFilterAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Concierge::query()
                    ->with(['user', 'user.referrer'])
                    ->withCount([
                        'bookings as total_bookings' => function (Builder $query) {
                            $this->applyDateRangeToQuery($query);
                        },
                        'bookings as cancelled_bookings' => function (Builder $query) {
                            $this->applyDateRangeToQuery($query);
                            $query->where('status', 'cancelled');
                        },
                        'bookings as no_show_bookings' => function (Builder $query) {
                            $this->applyDateRangeToQuery($query);
                            $query->where('status', 'no_show');
                        },
                    ])
                    ->withAvg([
                        'bookings as average_diners' => function (Builder $query) {
                            $this->applyDateRangeToQuery($query);
                            $query->whereIn('status', ['confirmed', 'venue_confirmed']);
                        },
                    ], 'guest_count')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Concierge Name')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('user', function (Builder $query) use ($search) {
                        $query->where('users.first_name', 'like', "%{$search}%")
                            ->orWhere('users.last_name', 'like', "%{$search}%");
                    }))
                    ->sortable(),
                TextColumn::make('hotel_name')
                    ->label('Hotel Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.referrer.name')
                    ->label('Referrer')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('user.referrer', function (Builder $query) use ($search) {
                        $query->where('users.first_name', 'like', "%{$search}%")
                            ->orWhere('users.last_name', 'like', "%{$search}%");
                    }))
                    ->sortable(),
                TextColumn::make('total_bookings')
                    ->label('# of Bookings')
                    ->sortable(),
                TextColumn::make('cancelled_bookings')
                    ->label('# of Cancellations')
                    ->sortable(),
                TextColumn::make('no_show_bookings')
                    ->label('# of No Shows')
                    ->sortable(),
                TextColumn::make('average_diners')
                    ->label('Avg. Number of Diners')
                    ->formatStateUsing(fn ($state) => number_format($state, 1))
                    ->sortable(),
            ])
            ->defaultSort('total_bookings', 'desc')
            ->striped();
    }

    private function applyDateRangeToQuery(Builder $query): void
    {
        if (isset($this->filters['startDate'], $this->filters['endDate'])) {
            $query->whereBetween('booking_at', [
                Carbon::parse($this->filters['startDate'])->startOfDay(),
                Carbon::parse($this->filters['endDate'])->endOfDay(),
            ]);
        }
    }

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->isLoading = true;
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
        $this->isLoading = false;
    }
}
