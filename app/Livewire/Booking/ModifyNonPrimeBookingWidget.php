<?php

namespace App\Livewire\Booking;

use App\Actions\Booking\SendModificationRequestToVenueContacts;
use App\Models\Booking;
use App\Models\BookingModificationRequest;
use App\Models\ScheduleWithBookingMV;
use App\Notifications\Booking\CustomerModificationRequested;
use Exception;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class ModifyNonPrimeBookingWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.booking.modify-non-prime-booking-widget';

    public ?Booking $booking = null;

    public ?array $data = [];

    public array $bookingDetails = [];

    public array $availableSlots = [];

    public ?int $selectedTimeSlotId = null;

    protected ?int $pendingGuestCount = null;

    protected $listeners = ['booking-modified' => '$refresh'];

    public bool $showDetails = true;

    public function mount(): void
    {
        if ($this->booking) {
            $this->bookingDetails = [
                'guest_name' => $this->booking->guest_name,
                'venue_name' => $this->booking->venue->name,
                'current_time' => $this->booking->booking_at->format('g:i A'),
                'guest_count' => $this->booking->guest_count,
                'booking_date' => $this->booking->booking_at->format('M d, Y'),
            ];

            $this->pendingGuestCount = $this->booking->guest_count;
            $this->selectedTimeSlotId = $this->booking->schedule_template_id;

            $this->form->fill([
                'guest_count' => $this->booking->guest_count,
            ]);

            $this->loadAvailableSlots();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns($this->showDetails ? 2 : 1)
                    ->schema([
                        ViewField::make('booking_details')
                            ->view('livewire.booking.partials.booking-details')
                            ->viewData([
                                'details' => $this->bookingDetails,
                            ])
                            ->hidden(! $this->showDetails)
                            ->columnSpan($this->showDetails ? 2 : 1),
                        Select::make('guest_count')
                            ->label('Party Size')
                            ->options([
                                2 => '2 Guests',
                                3 => '3 Guests',
                                4 => '4 Guests',
                                5 => '5 Guests',
                                6 => '6 Guests',
                                7 => '7 Guests',
                                8 => '8 Guests',
                            ])
                            ->default(fn () => $this->pendingGuestCount ?? 2)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->pendingGuestCount = $state;
                                $this->selectedTimeSlotId = null;
                                $this->loadAvailableSlots();
                            })
                            ->columnSpan($this->showDetails ? 1 : 'full'),
                    ]),
            ])
            ->statePath('data');
    }

    public function selectTimeSlot(int $scheduleTemplateId): void
    {
        $this->selectedTimeSlotId = $scheduleTemplateId;
        $this->loadAvailableSlots();
    }

    public function getHasChangesProperty(): bool
    {
        $formState = $this->form->getState();
        $guestCount = $this->pendingGuestCount ?? intval($formState['guest_count']);

        return filled($this->selectedTimeSlotId) && ($guestCount !== $this->booking?->guest_count ||
                $this->selectedTimeSlotId !== $this->booking?->schedule_template_id);
    }

    protected function loadAvailableSlots(): void
    {
        if (! $this->booking) {
            return;
        }

        // Use form state for guest count, fallback to original booking guest count
        $formState = $this->form->getState();
        $guestCount = $this->pendingGuestCount ?? intval($formState['guest_count']) ?? $this->booking->guest_count;

        // Round up guest count to nearest table size (2,4,6,8)
        $tableSize = match (true) {
            $guestCount <= 2 => 2,
            $guestCount <= 4 => 4,
            $guestCount <= 6 => 6,
            default => 8,
        };

        $schedules = ScheduleWithBookingMV::query()
            ->with('venue')
            ->where('venue_id', $this->booking->venue->id)
            ->where('booking_date', $this->booking->booking_at->format('Y-m-d'))
            ->where('party_size', $tableSize)  // Use rounded up table size
            ->where('prime_time', false)
            ->where('remaining_tables', '>', 0)
            ->where('is_available', true)
            ->when($this->booking->booking_at->isToday() && now()->timezone($this->booking->venue->timezone)->format('Y-m-d') === $this->booking->booking_at->format('Y-m-d'),
                function (Builder $query) {
                    $query->where('start_time', '>', now()->timezone($this->booking->venue->timezone)->format('H:i:s'));
                })
            ->orderBy('start_time')
            ->get();

        $this->availableSlots = $schedules->map(fn ($schedule) => [
            'id' => $schedule->schedule_template_id,
            'time' => $schedule->formatted_start_time,
            'remaining_tables' => $schedule->remaining_tables,
            'is_current' => $schedule->start_time === $this->booking->booking_at->format('H:i:s'),
            'is_selected' => $schedule->schedule_template_id === $this->selectedTimeSlotId,
        ])->toArray();
    }

    public function submitModificationRequest(): void
    {
        if (! $this->hasChanges) {
            Notification::make()
                ->warning()
                ->title('No Changes Detected')
                ->body('Please make changes to submit a modification request.')
                ->send();

            return;
        }

        try {
            $formState = $this->form->getState();
            $schedule = $this->selectedTimeSlotId ? ScheduleWithBookingMV::query()->find($this->selectedTimeSlotId) : null;

            // Determine request source and user
            $requestSource = auth()->check() ? auth()->user()->main_role : 'customer';
            $requestedById = auth()->check()
                ? auth()->id()
                : $this->booking->concierge_id; // Default to booking's concierge

            // Create modification request
            $modificationRequest = BookingModificationRequest::query()->create([
                'booking_id' => $this->booking->id,
                'requested_by_id' => $requestedById,
                'original_guest_count' => $this->booking->guest_count,
                'requested_guest_count' => $formState['guest_count'],
                'original_time' => $this->booking->booking_at->format('H:i:s'),
                'requested_time' => $schedule ? $schedule->start_time : $this->booking->booking_at->format('H:i:s'),
                'original_schedule_template_id' => $this->booking->schedule_template_id,
                'requested_schedule_template_id' => $this->selectedTimeSlotId ?? $this->booking->schedule_template_id,
                'status' => 'pending',
                'meta' => [
                    'request_source' => $requestSource,
                    'request_source_id' => auth()->id(), // Will be null for customer requests
                    'customer_notified' => false,
                    'venue_notified' => false,
                    'customer_initiated' => ! auth()->check(), // Flag to indicate customer initiated request
                ],
            ]);

            activity()
                ->performedOn($this->booking)
                ->withProperties([
                    'modification_request_id' => $modificationRequest->id,
                    'original_time' => $this->booking->booking_at->format('g:i A'),
                    'requested_time' => $schedule ? $schedule->formatted_start_time : $this->booking->booking_at->format('g:i A'),
                    'original_guest_count' => $this->booking->guest_count,
                    'requested_guest_count' => $formState['guest_count'],
                    'venue_id' => $this->booking->venue->id,
                    'venue_name' => $this->booking->venue->name,
                    'request_source' => $requestSource,
                ])
                ->log('Requested non-prime booking modification');

            $modificationRequest->notify(new CustomerModificationRequested);

            SendModificationRequestToVenueContacts::run($modificationRequest);

            // Notify venue of modification request
            Notification::make()
                ->success()
                ->title('Change Request Submitted')
                ->body('The venue will be notified and respond shortly.')
                ->send();

            // Close the modal
            $this->dispatch('close-modal', id: 'modify-booking-'.$this->booking->id);

            // Reset form
            $this->selectedTimeSlotId = null;
            $this->loadAvailableSlots();
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function loadBookingDetails(): void
    {
        $this->bookingDetails = [
            'guest_name' => $this->booking->guest_name,
            'venue_name' => $this->booking->venue->name,
            'current_time' => $this->booking->booking_at->format('g:i A'),
            'guest_count' => $this->booking->guest_count,
            'booking_date' => $this->booking->booking_at->format('M d, Y'),
        ];
    }
}
