<?php

namespace App\Filament\Pages;

use App\Models\Venue;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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

    public bool $showResults = false;

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
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('venue_id')
                    ->label('Venue')
                    ->options($this->getVenueOptions())
                    ->required()
                    ->searchable()
                    ->placeholder('Select a venue with CoverManager'),

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
            ->statePath('data')
            ->columns(3);
    }

    protected function getVenueOptions(): array
    {
        return Venue::whereHas('platforms', function ($query) {
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

        $venue = Venue::find($this->data['venue_id']);
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
            $date = Carbon::parse($this->data['date']);
            $partySize = (int) $this->data['party_size'];

            $availability = $coverManagerService->checkAvailabilityRaw(
                $restaurantId,
                $date,
                '19:00', // Default time for the API call
                $partySize
            );

            if (empty($availability)) {
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

            Notification::make()
                ->title('Availability Checked')
                ->body('Results updated below')
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to check availability: '.$e->getMessage())
                ->danger()
                ->send();
        }
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
            usort($parsed, function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            });

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
        usort($parsed, function ($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

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
        $this->availabilityData = [];
    }
}
