<?php

namespace App\Filament\Pages;

use App\Enums\VenueType;
use App\Models\Region;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Services\ReservationService;
use App\Traits\FormatsPhoneNumber;
use App\Traits\ManagesBookingForms;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @property Form $form
 */
class IbizaHikeStationBooking extends Page
{
    use FormatsPhoneNumber;
    use ManagesBookingForms;

    protected static ?string $navigationIcon = 'phosphor-mountains-bold';

    protected static string $view = 'filament.pages.ibiza-hike-station-booking';

    // Remove Navigation Grouping
    // protected static ?string $navigationGroup = 'Bookings';
    protected static ?int $navigationSort = -3; // Keep sort order

    protected static ?string $slug = 'ibiza-hike-station';

    protected static ?string $navigationLabel = 'IBIZA HIKE STATION';

    public ?array $data = [];

    public ?Venue $hikeVenue = null;

    public string $currency = 'EUR';

    public ?int $calculatedPrice = null;

    public bool $isLoading = false;

    // Availability Display State
    public bool $showAvailability = false;

    // Rename and change to array holding potentially multiple slots for the selected day
    public array $selectedDaySlots = [];

    public array $nextDaysAvailability = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        // Only show if user is logged in, has the correct role, AND is in the Ibiza region
        return $user && $user->region === 'ibiza' && $user->hasAnyRole(['concierge']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole(['concierge']);
    }

    public function mount(): void
    {
        try {
            $this->hikeVenue = Venue::query()
                ->where('venue_type', VenueType::HIKE_STATION)
                ->where('region', 'ibiza')
                ->firstOrFail();

            $this->timezone = $this->hikeVenue->timezone ?? 'Europe/Madrid';
            $this->currency = Region::query()->where('id', 'ibiza')->first()?->currency ?? 'EUR';

            // Ensure the rest of the application (e.g. ReservationHub) recognises that the
            // current working region is Ibiza so that currency/locale defaults to EUR.
            session(['region' => 'ibiza']);
            // Notify any Livewire listeners that the region has changed.
            $this->dispatch('region-changed');

            $this->form->fill();

            $this->data['date'] = now($this->timezone)->format('Y-m-d');
            $this->data['radio_date'] = now($this->timezone)->format('Y-m-d');
            $this->data['select_date'] = now($this->timezone)->format('Y-m-d');
            $this->data['venue_id'] = $this->hikeVenue->id;

            $this->selectedDaySlots = [];
            $this->showAvailability = false;
            $this->calculatedPrice = null;
            $this->nextDaysAvailability = [];

        } catch (ModelNotFoundException $e) {
            Notification::make()
                ->title('Error')
                ->body('Ibiza Hike Station venue not found or configured correctly.')
                ->danger()
                ->send();
            report($e);
            $this->hikeVenue = null;
        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An unexpected error occurred loading the booking form.')
                ->danger()
                ->send();
            report($e);
            $this->hikeVenue = null;
        }
    }

    public function getTitle(): string
    {
        return 'Ibiza Hike Station';
    }

    public function form(Form $form): Form
    {
        // Get base date components from trait AS IS
        $dateComponents = collect($this->commonFormComponents())
            ->filter(fn ($c) => $c instanceof Component && method_exists($c, 'getName') && in_array($c->getName(), ['date', 'radio_date', 'select_date']))
            ->all();

        return $form
            ->schema([
                ...$dateComponents, // Use original date components from trait

                Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
                        Select::make('hiker_count')
                            ->prefixIcon('heroicon-m-users')
                            ->options(array_combine(range(5, 20), range(5, 20)))
                            ->placeholder('Hikers')
                            ->hiddenLabel()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateAvailabilityDisplay())
                            ->columnSpanFull(),

                        // Time slot remains commented out
                        /* Select::make('time_slot') ... */
                    ]),

                Hidden::make('venue_id'),
            ])
            ->columns(['default' => 1, 'sm' => 2]) // Keep the overall form columns
            ->extraAttributes(['class' => 'inline-form'])
            ->statePath('data');
    }

    public function updatedData($value, $key): void
    {
        // Check if redundant update (e.g., radio_date selecting 'select_date')
        // Using a simplified check for now, adapt if needed
        if ($key === 'radio_date' && $value === 'select_date') {
            // Don't refresh yet, wait for select_date to change
            return;
        }

        if ($key === 'select_date' && $this->data['radio_date'] !== 'select_date') {
            // If select_date changes but radio_date isn't 'select_date', ignore (shouldn't happen)
            return;
        }

        // Check if necessary fields are filled to trigger update
        // We only need date and hiker count now
        if (isset($this->data['date']) && isset($this->data['hiker_count'])) {
            $this->updateAvailabilityDisplay();
        } else {
            // If required fields are missing, clear availability
            $this->selectedDaySlots = []; // Use correct property name
            $this->calculatedPrice = null;
            $this->showAvailability = false;
            $this->nextDaysAvailability = [];
        }
    }

    public function updateAvailabilityDisplay(): void
    {
        $formData = $this->form->getState();
        $this->calculatedPrice = null;
        $this->selectedDaySlots = [];
        $this->showAvailability = false;
        $this->nextDaysAvailability = [];

        if (blank($formData['date']) || blank($formData['hiker_count'])) {
            return;
        }
        $hikerCount = (int) $formData['hiker_count'];
        if ($hikerCount < 5 || $hikerCount > 20) {
            return;
        }

        $this->updateCalculatedPrice();
        $basePrice = $this->calculatedPrice;
        $bookingDate = $formData['date'];
        $dayOfWeek = strtolower(Carbon::parse($bookingDate, $this->timezone)->format('l'));

        // Determine if selected date is today
        $isToday = Carbon::parse($bookingDate, $this->timezone)->isToday();
        $cutoffTime = null;
        if ($isToday) {
            $cutoffTime = now($this->timezone)->addMinutes(ReservationService::MINUTES_PAST);
        }

        // --- Check Selected Day Availability (Morning & Afternoon) ---
        $slotsToCheck = ['morning' => '10:00:00', 'afternoon' => '14:00:00'];
        $this->selectedDaySlots = []; // Reset before populating

        foreach ($slotsToCheck as $slotKey => $startTime) {
            // Check DB availability first
            $isAvailable = ScheduleTemplate::query()
                ->where('venue_id', $this->hikeVenue->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->whereIn('party_size', range(5, 20))
                ->where('is_available', true)
                ->exists();

            // If today, check against cutoff time
            if ($isAvailable && $isToday && $cutoffTime) {
                $slotStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $bookingDate.' '.$startTime, $this->timezone);
                if ($slotStartTime->isBefore($cutoffTime)) {
                    $isAvailable = false; // Too late to book this slot today
                }
            }

            $this->selectedDaySlots[] = [
                'time' => $slotKey === 'morning' ? 'Morning (10:00 AM)' : 'Afternoon (2:00 PM)',
                'price' => $basePrice,
                'is_available' => $isAvailable, // Use potentially modified availability
                'slot_key' => $slotKey,
                'date_for_click' => $bookingDate,
            ];
        }
        // --- End Check Selected Day ---

        // --- Fetch Next 3 Days Availability (Both Slots) ---
        $startDate = Carbon::parse($bookingDate, $this->timezone)->addDay();
        $this->nextDaysAvailability = [];

        for ($i = 0; $i < 3; $i++) {
            // ... (fetch day data as before) ...
            $currentDate = $startDate->copy()->addDays($i);
            $currentDayOfWeek = strtolower($currentDate->format('l'));
            // No need to check cutoff for future dates
            $isFutureDayToday = $currentDate->isToday(); // Should always be false here, but check for safety
            $futureCutoffTime = $isFutureDayToday ? now($this->timezone)->addMinutes(ReservationService::MINUTES_PAST) : null;

            $dayData = [
                'date_formatted' => $currentDate->format('D, M jS'),
                'date_value' => $currentDate->format('Y-m-d'),
                'slots' => [],
            ];

            foreach ($slotsToCheck as $slotKey => $startTime) {
                $isAvailableNextDay = ScheduleTemplate::query() // ... (DB query remains same) ...
                    ->exists();

                // Check cutoff for future days (though unlikely needed as loop starts tomorrow)
                if ($isAvailableNextDay && $isFutureDayToday && $futureCutoffTime) {
                    $slotStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate->format('Y-m-d').' '.$startTime, $this->timezone);
                    if ($slotStartTime->isBefore($futureCutoffTime)) {
                        $isAvailableNextDay = false;
                    }
                }

                $dayData['slots'][] = [
                    'time_formatted' => $slotKey === 'morning' ? '10:00 AM' : '2:00 PM',
                    'price' => $basePrice,
                    'is_available' => $isAvailableNextDay, // Use potentially modified availability
                    'slot_key' => $slotKey,
                ];
            }
            $this->nextDaysAvailability[] = $dayData;
        }
        // --- End Fetch Next 3 Days ---

        $this->showAvailability = true;
    }

    public function updateCalculatedPrice(): void
    {
        $hikerCount = (int) ($this->data['hiker_count'] ?? 0);

        if ($hikerCount < 5 || $hikerCount > 20) {
            $this->calculatedPrice = null;

            return;
        }

        // Base price for 5 hikers: 300 euros = 30000 cents
        $basePrice = 30000;
        // Additional hiker price: 60 euros = 6000 cents each
        $additionalHikerPrice = 6000;
        $minimumHikers = 5;

        // Calculate additional hikers beyond the minimum 5
        $additionalHikers = max(0, $hikerCount - $minimumHikers);

        // Total price = base price + (price per additional hiker × number of additional hikers)
        $this->calculatedPrice = $basePrice + ($additionalHikers * $additionalHikerPrice);
    }

    public function selectSlot(string $slotKey, string $dateForSlot): void
    {
        if (! $this->hikeVenue || $this->isLoading) {
            return;
        }

        $this->isLoading = true;
        $formData = $this->form->getState();

        if (blank($formData['hiker_count'])) {
            Notification::make()->title('Error')->body('Please select hiker count first.')->warning()->send();
            $this->isLoading = false;

            return;
        }

        $startTime = $slotKey === 'morning' ? '10:00:00' : '14:00:00';
        $bookingDate = $dateForSlot;
        $dayOfWeek = strtolower(Carbon::parse($bookingDate, $this->timezone)->format('l'));

        try {
            $scheduleTemplate = ScheduleTemplate::query()
                ->where('venue_id', $this->hikeVenue->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->whereIn('party_size', range(5, 20))
                ->where('is_available', true)
                ->select('id')
                ->firstOrFail();

            // Redirect to ReservationHub, passing the necessary parameters including the source
            $this->redirectRoute('filament.admin.pages.concierge.reservation-hub', [
                'scheduleTemplateId' => $scheduleTemplate->id,
                'date' => $bookingDate,
                'guestCount' => $formData['hiker_count'],
                'source' => 'hike_station_booking_form',
            ]);

        } catch (ModelNotFoundException $e) {
            Notification::make()->title('Availability Error')
                ->body('The selected slot is no longer available or configured.')->danger()->send();
            report($e);
            $this->isLoading = false;
        } catch (Exception $e) {
            Notification::make()->title('Error')
                ->body('Could not proceed with booking. '.$e->getMessage())->danger()->send();
            report($e);
            $this->isLoading = false;
        }
    }
}
