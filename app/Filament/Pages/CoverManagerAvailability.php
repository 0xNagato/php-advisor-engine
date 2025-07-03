<?php

namespace App\Filament\Pages;

use App\Models\Venue;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Database\Query\Builder;
use Throwable;

class CoverManagerAvailability extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.cover-manager-availability';

    protected static ?string $navigationLabel = 'CoverManager Availability';

    protected static ?string $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public ?array $availabilityData = [];

    public ?array $calendarData = [];

    public bool $showResults = false;

    public bool $showCalendarResults = false;

    public array $expandedDates = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            'date' => Carbon::today()->format('Y-m-d'),
            'party_size' => 2,
            'use_calendar_mode' => false,
            'date_start' => Carbon::today()->format('Y-m-d'),
            'date_end' => Carbon::today()->addDays(6)->format('Y-m-d'),
            'discount' => 'all',
            'product_type' => '1',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General Settings')
                    ->schema([
                        Select::make('venue_id')
                            ->label('Venue')
                            ->options($this->getVenueOptions())
                            ->required()
                            ->searchable()
                            ->placeholder('Select a venue with CoverManager'),

                        Toggle::make('use_calendar_mode')
                            ->label('Use Calendar Mode')
                            ->helperText('Switch between single date availability check and calendar range check')
                            ->reactive()
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Single Date Availability')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->minDate(Carbon::today())
                            ->maxDate(Carbon::today()->addMonths(3))
                            ->default(Carbon::today()),

                        TextInput::make('party_size')
                            ->label('Party Size (for API call)')
                            ->helperText('Used for the API call, but results will show all available party sizes')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->default(2),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => ! $get('use_calendar_mode')),

                Section::make('Calendar Availability')
                    ->schema([
                        DatePicker::make('date_start')
                            ->label('Start Date')
                            ->helperText('Optional: Leave empty to get default range')
                            ->minDate(Carbon::today())
                            ->maxDate(Carbon::today()->addMonths(6)),

                        DatePicker::make('date_end')
                            ->label('End Date')
                            ->helperText('Optional: Leave empty to get default range')
                            ->minDate(Carbon::today())
                            ->maxDate(Carbon::today()->addMonths(6)),

                        Select::make('discount')
                            ->label('Discount Filter')
                            ->options([
                                'all' => 'All (default)',
                                '0' => 'No discount',
                                '1' => 'With discounts',
                            ])
                            ->default('all')
                            ->required(),

                        Select::make('product_type')
                            ->label('Product Type')
                            ->options([
                                '0' => 'No show product type',
                                '1' => 'Show products (default)',
                            ])
                            ->default('1')
                            ->required(),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('use_calendar_mode')),
            ])
            ->statePath('data');
    }

    protected function getVenueOptions(): array
    {
        return Venue::query()->whereHas('platforms', function (Builder $query) {
            $query->where('platform_type', 'covermanager')
                ->where('is_enabled', true);
        })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function checkAvailability(): void
    {
        $this->validate();

        $venue = Venue::query()->find($this->data['venue_id']);
        if (! $venue) {
            Notification::make()
                ->title('Error')
                ->body('Venue not found')
                ->danger()
                ->send();

            return;
        }

        $platform = $venue->getPlatform('covermanager');
        if (! $platform || ! $platform->is_enabled) {
            Notification::make()
                ->title('Error')
                ->body('CoverManager is not enabled for this venue')
                ->danger()
                ->send();

            return;
        }

        $restaurantId = $platform->getConfig('restaurant_id');
        if (! $restaurantId) {
            Notification::make()
                ->title('Error')
                ->body('CoverManager restaurant ID not configured for this venue')
                ->danger()
                ->send();

            return;
        }

        try {
            $coverManagerService = app(CoverManagerService::class);

            // Check if we're in calendar mode
            if ($this->data['use_calendar_mode']) {
                $this->checkCalendarAvailability($venue, $coverManagerService, $restaurantId);
            } else {
                $this->checkSingleDateAvailability($venue, $coverManagerService, $restaurantId);
            }

        } catch (Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to check availability: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function checkSingleDateAvailability(Venue $venue, CoverManagerService $coverManagerService, string $restaurantId): void
    {
        $date = Carbon::parse($this->data['date']);
        $partySize = (int) $this->data['party_size'];

        $availability = $coverManagerService->checkAvailabilityRaw(
            $restaurantId,
            $date,
            '19:00', // Default time for the API call
            $partySize
        );

        if (blank($availability)) {
            Notification::make()
                ->title('No Response')
                ->body('No availability data returned from CoverManager')
                ->warning()
                ->send();

            return;
        }

        // Handle CoverManager error response
        if (isset($availability['resp']) && $availability['resp'] === 0) {
            $error = $availability['error'] ?? $availability['status'] ?? 'Unknown error';
            Notification::make()
                ->title('CoverManager API Error')
                ->body($error)
                ->danger()
                ->send();

            return;
        }

        $this->availabilityData = [
            'venue_name' => $venue->name,
            'restaurant_id' => $restaurantId,
            'date' => $date->format('Y-m-d (l)'),
            'party_size' => $partySize,
            'all_party_sizes' => $this->parseAllPartySizes($availability),
            'raw_data' => $availability,
        ];

        $this->showResults = true;
        $this->showCalendarResults = false;

        Notification::make()
            ->title('Availability Checked')
            ->body('Results updated below')
            ->success()
            ->send();
    }

    protected function checkCalendarAvailability(Venue $venue, CoverManagerService $coverManagerService, string $restaurantId): void
    {
        $dateStart = $this->data['date_start'] ? Carbon::parse($this->data['date_start']) : null;
        $dateEnd = $this->data['date_end'] ? Carbon::parse($this->data['date_end']) : null;
        $discount = $this->data['discount'];
        $productType = $this->data['product_type'];

        $calendar = $coverManagerService->checkAvailabilityCalendarRaw(
            $restaurantId,
            $dateStart,
            $dateEnd,
            $discount,
            $productType
        );

        if (blank($calendar)) {
            Notification::make()
                ->title('No Response')
                ->body('No calendar data returned from CoverManager')
                ->warning()
                ->send();

            return;
        }

        // Handle CoverManager error response
        if (isset($calendar['resp']) && $calendar['resp'] === 0) {
            $error = $calendar['error'] ?? $calendar['status'] ?? 'Unknown error';
            Notification::make()
                ->title('CoverManager API Error')
                ->body($error)
                ->danger()
                ->send();

            return;
        }

        $this->calendarData = [
            'venue_name' => $venue->name,
            'restaurant_id' => $restaurantId,
            'date_start' => $dateStart?->format('Y-m-d'),
            'date_end' => $dateEnd?->format('Y-m-d'),
            'discount' => $discount,
            'product_type' => $productType,
            'calendar_summary' => $this->parseCalendarSummary($calendar),
            'raw_data' => $calendar,
        ];

        $this->showCalendarResults = true;
        $this->showResults = false;

        Notification::make()
            ->title('Calendar Availability Checked')
            ->body('Calendar results updated below')
            ->success()
            ->send();
    }

    protected function parseAllPartySizes(array $availability): array
    {
        if (! isset($availability['availability']['people'])) {
            return [];
        }

        $allPartySizes = [];

        foreach ($availability['availability']['people'] as $partySize => $timeSlots) {
            $parsed = [];

            foreach ($timeSlots as $time => $details) {
                $parsed[] = [
                    'time' => $time,
                    'has_discount' => $details['discount'] ?? false,
                ];
            }

            // Sort by time
            usort($parsed, fn ($a, $b) => strcmp((string) $a['time'], (string) $b['time']));

            $allPartySizes[(int) $partySize] = $parsed;
        }

        // Sort party sizes numerically
        ksort($allPartySizes);

        return $allPartySizes;
    }

    protected function parseAvailabilityData(array $availability, int $partySize): array
    {
        if (! isset($availability['availability']['people'][(string) $partySize])) {
            return [];
        }

        $timeSlots = $availability['availability']['people'][(string) $partySize];
        $parsed = [];

        foreach ($timeSlots as $time => $details) {
            $parsed[] = [
                'time' => $time,
                'has_discount' => $details['discount'] ?? false,
            ];
        }

        // Sort by time
        usort($parsed, fn ($a, $b) => strcmp((string) $a['time'], (string) $b['time']));

        return $parsed;
    }

    public function getAvailablePartySizes(): array
    {
        if (! $this->showResults || ! isset($this->availabilityData['raw_data']['availability']['people'])) {
            return [];
        }

        return array_keys($this->availabilityData['raw_data']['availability']['people']);
    }

    public function resetResults(): void
    {
        $this->showResults = false;
        $this->showCalendarResults = false;
        $this->availabilityData = [];
        $this->calendarData = [];
        $this->expandedDates = [];
    }

    protected function parseCalendarSummary(array $calendar): array
    {
        if (! isset($calendar['calendar'])) {
            return [];
        }

        $summary = [];

        foreach ($calendar['calendar'] as $date => $dayData) {
            $totalSlots = 0;
            $partySizes = [];
            $timeSlots = [];

            // Count available slots by party size
            if (isset($dayData['people']) && is_array($dayData['people'])) {
                foreach ($dayData['people'] as $partySize => $slots) {
                    $partySizes[] = (int) $partySize;
                    $totalSlots += count($slots);
                }
            }

            // Get unique time slots
            if (isset($dayData['hours']) && is_array($dayData['hours'])) {
                $timeSlots = array_keys($dayData['hours']);
                sort($timeSlots);
            }

            $summary[$date] = [
                'has_availability' => $totalSlots > 0,
                'total_slots' => $totalSlots,
                'party_sizes' => array_unique($partySizes),
                'time_slots' => $timeSlots,
                'min_party_size' => filled($partySizes) ? min($partySizes) : null,
                'max_party_size' => filled($partySizes) ? max($partySizes) : null,
                'first_available_time' => filled($timeSlots) ? $timeSlots[0] : null,
                'last_available_time' => filled($timeSlots) ? end($timeSlots) : null,
            ];
        }

        return $summary;
    }

    public function toggleDateExpansion(string $date): void
    {
        if (in_array($date, $this->expandedDates)) {
            $this->expandedDates = array_diff($this->expandedDates, [$date]);
        } else {
            $this->expandedDates[] = $date;
        }
    }
}
