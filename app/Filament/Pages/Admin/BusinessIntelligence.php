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
use Illuminate\Support\HtmlString;
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
                    ->select('concierges.*')
                    ->selectRaw('COUNT(CASE WHEN bookings.status = ? THEN 1 END) as cancelled_bookings', ['cancelled'])
                    ->selectRaw('COUNT(CASE WHEN bookings.status = ? THEN 1 END) as no_show_bookings', ['no_show'])
                    ->selectRaw('COUNT(CASE WHEN bookings.status = ? THEN 1 END) as total_bookings', ['confirmed'])
                    ->selectRaw('AVG(CASE WHEN bookings.status = ? THEN bookings.guest_count END) as average_diners', ['confirmed'])
                    ->selectRaw('ROUND(CAST((COUNT(CASE WHEN bookings.status IN (?, ?) THEN 1 END) * 100.0) / NULLIF(COUNT(bookings.id), 0) AS DECIMAL(5,1)), 1) as problem_percentage', ['cancelled', 'no_show'])
                    ->leftJoin('bookings', 'concierges.id', '=', 'bookings.concierge_id')
                    ->when(isset($this->filters['startDate'], $this->filters['endDate']), function ($query) {
                        $query->whereBetween('bookings.booking_at', [
                            Carbon::parse($this->filters['startDate'])->startOfDay(),
                            Carbon::parse($this->filters['endDate'])->endOfDay(),
                        ]);
                    })
                    ->groupBy('concierges.id')
            )
            ->recordUrl(fn (Concierge $record) => route('filament.admin.pages.booking-search', [
                'filters' => [
                    'concierge_search' => $record->user->name,
                    'start_date' => $this->filters['startDate'] ?? null,
                    'end_date' => $this->filters['endDate'] ?? null,
                ],
            ]))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Concierge')
                    ->formatStateUsing(fn ($record) => new HtmlString(<<<HTML
                            <div class="space-y-0 text-xs">
                                <div class="font-medium">{$record->user->name}</div>
                                <div class="text-gray-500">{$record->hotel_name}</div>
                                <div class="text-gray-500">{$record->user->referrer?->name}</div>
                            </div>
                        HTML))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('user', function (Builder $query) use ($search) {
                        $query->where('users.first_name', 'like', "%{$search}%")
                            ->orWhere('users.last_name', 'like', "%{$search}%");
                    }))
                    ->sortable(),
                TextColumn::make('total_bookings')
                    ->label('Bookings')
                    ->size('xs')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('cancelled_bookings')
                    ->label('Cancellations')
                    ->size('xs')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('no_show_bookings')
                    ->label('No Shows')
                    ->size('xs')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('average_diners')
                    ->label('Avg. Diners')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1))
                    ->size('xs')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('problem_percentage')
                    ->label('Problem %')
                    ->formatStateUsing(fn ($state) => $state ? $state.'%' : '0%')
                    ->size('xs')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->defaultSort('total_bookings', 'desc')
            ->striped();
    }

    private function applyDateRangeToQuery(Builder $query): void
    {
        if (isset($this->filters['startDate'], $this->filters['endDate'])) {
            $query->whereBetween('bookings.booking_at', [
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
