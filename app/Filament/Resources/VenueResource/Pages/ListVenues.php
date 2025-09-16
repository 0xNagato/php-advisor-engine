<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Events\BookingCancelled;
use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Filament\Resources\VenueResource;
use App\Models\Booking;
use App\Models\Region;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Services\Booking\BookingCalculationService;
use App\Services\ReservationService;
use App\Traits\HandlesPartySizeMapping;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListVenues extends ListRecords
{
    use HandlesPartySizeMapping;
    use ImpersonatesOther;

    protected static string $resource = VenueResource::class;

    protected static string $view = 'filament.pages.venue.list-venues';

    public ?string $tableSortColumn = 'user.secured_at';

    public ?string $tableSortDirection = 'desc';

    const bool USE_SLIDE_OVER = false;

    // Variables for booking filter
    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $currentVenueId = null;

    public ?string $currentVenueName = null;

    public ?string $region = null;

    // Filter for showing only venues with bookings
    public bool $onlyWithBookings = false;

    // Filter for showing only active status venues
    public bool $onlyActiveStatus = false;

    public ?array $statuses = null;

    // Customer search query
    public ?string $customerSearch = null;

    // Bulk status update
    public string $bulkIdsInput = '';

    public string $bulkStatus = '';

    // Flag for showing edit form in the modal
    public bool $showEditForm = false;

    // Flag for select all bookings
    public bool $shouldSelectAllBookings = false;

    // Selected bookings and form data
    public array $selectedBookings = [];

    public array $bulkEditData = [
        'guest_first_name' => '',
        'guest_last_name' => '',
        'guest_email' => '',
        'guest_phone' => '',
        'guest_count' => 1,
        'booking_date' => null,
        'booking_time' => null,
        'status' => '',
        'send_confirmation' => false,
    ];

    /**
     * Generate time slots in 30-minute intervals for dropdown.
     */
    #[Computed]
    public function timeSlots(): array
    {
        $slots = [];
        $start = Carbon::today()->startOfDay();
        $end = Carbon::today()->endOfDay();

        while ($start->lessThan($end)) {
            $slots[$start->format('H:i')] = $start->format('h:i A'); // Use H:i for value, h:i A for display
            $start->addMinutes(30);
        }

        return $slots;
    }

    /**
     * Get all available regions for the filter dropdown.
     */
    #[Computed]
    public function availableRegions(): array
    {
        return Region::all()->pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function filteredBookings()
    {
        // Skip the notification if we're just loading the page and not inside a modal
        $isLoadingDataForModal = session()->has('bulk_edit_modal_open') || request()->has('bulkEditModalOpen');

        if (! $this->currentVenueId) {
            // Only show notification if we're actively trying to load booking data
            if ($isLoadingDataForModal) {
                Notification::make()
                    ->title('Debug: No venue ID provided')
                    ->body('Please select a venue first or try again')
                    ->warning()
                    ->send();
            }

            return collect();
        }

        $venue = Venue::query()->find($this->currentVenueId);

        if (! $venue) {
            Notification::make()
                ->title('Venue not found')
                ->warning()
                ->send();

            return collect();
        }

        // Don't use the venue's bookings relationship directly as it filters by status
        // Instead, use the hasManyThrough method and create a custom query
        $query = $venue->hasManyThrough(
            Booking::class,
            ScheduleTemplate::class
        );

        // Exclude abandoned bookings by default, but show all other statuses including cancelled
        $query->where('status', '!=', BookingStatus::ABANDONED->value);

        // If date range is provided, filter by date
        if ($this->startDate && $this->endDate) {
            $userTimezone = auth()->user()?->timezone ?? config('app.default_timezone');

            $startDate = Carbon::parse($this->startDate, $userTimezone)
                ->startOfDay()
                ->setTimezone('UTC');

            $endDate = Carbon::parse($this->endDate, $userTimezone)
                ->endOfDay()
                ->setTimezone('UTC');

            $query->whereBetween('booking_at', [$startDate, $endDate]);
        }

        // If customer search is provided, filter by guest info
        if (filled($this->customerSearch)) {
            $searchTerm = '%'.$this->customerSearch.'%';
            $query->where(function ($query) use ($searchTerm) {
                $query->where('guest_first_name', 'like', $searchTerm)
                    ->orWhere('guest_last_name', 'like', $searchTerm)
                    ->orWhere('guest_email', 'like', $searchTerm)
                    ->orWhere('guest_phone', 'like', $searchTerm)
                    ->orWhereRaw("CONCAT(guest_first_name, ' ', guest_last_name) like ?", [$searchTerm]);
            });
        }

        // Get the result
        $result = $query->orderByDesc('booking_at')
            ->limit($this->startDate && $this->endDate ? 50 : 10) // Show 10 by default, 50 if date range
            ->get();

        return $result;
    }

    /**
     * Send booking confirmation notification to the customer
     */
    private function sendBookingConfirmation(Booking $booking): void
    {
        try {
            $booking->notify(new CustomerBookingConfirmed);

            activity()
                ->performedOn($booking)
                ->withProperties([
                    'guest_name' => $booking->guest_name,
                    'guest_phone' => $booking->guest_phone,
                    'guest_email' => $booking->guest_email,
                    'amount' => $booking->total_with_tax_in_cents,
                    'currency' => $booking->currency,
                    'sent_by' => auth()->user()->name,
                ])
                ->log('Booking confirmation resent to customer via bulk edit');
        } catch (Exception $e) {
            Notification::make()
                ->warning()
                ->title('Warning')
                ->body("Could not send confirmation for Booking #{$booking->id}: {$e->getMessage()}")
                ->send();
        }
    }

    /**
     * Bulk edit the selected bookings with the provided data
     */
    public function bulkEditBookings(): void
    {
        $editedCount = 0;
        $calculationService = app(BookingCalculationService::class);

        DB::beginTransaction();
        try {
            $bookings = Booking::query()->whereIn('id', $this->selectedBookings)->get();

            if ($bookings->isEmpty()) {
                DB::rollBack();
                Notification::make()
                    ->warning()
                    ->title('No Bookings Found')
                    ->body('No bookings were found to update.')
                    ->send();

                return;
            }

            foreach ($bookings as $booking) {
                $oldStatus = $booking->status;
                $oldGuestCount = $booking->guest_count;
                $oldBookingAt = $booking->booking_at;
                $oldScheduleTemplateId = $booking->schedule_template_id;

                // Combine date and time, validate
                if (blank($this->bulkEditData['booking_date']) || blank($this->bulkEditData['booking_time'])) {
                    Notification::make()
                        ->danger()
                        ->title('Validation Failed')
                        ->body("Booking date and time are required for booking #{$booking->id}.")
                        ->send();

                    continue; // Skip this booking
                }

                try {
                    $newBookingAt = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $this->bulkEditData['booking_date'].' '.$this->bulkEditData['booking_time']
                    );
                } catch (Exception) {
                    Notification::make()
                        ->danger()
                        ->title('Validation Failed')
                        ->body("Invalid date/time format for booking #{$booking->id}.")
                        ->send();

                    continue; // Skip this booking
                }

                $guestCount = (int) $this->bulkEditData['guest_count'];

                // Determine the target template party size using the trait method
                $targetPartySize = $this->getTargetPartySize($guestCount);

                if ($targetPartySize === null) {
                    Notification::make()
                        ->danger()
                        ->title('Update Failed')
                        ->body("Cannot accommodate party size of {$guestCount} for booking #{$booking->id}.")
                        ->send();

                    continue; // Skip this booking
                }

                // Ensure we have the current venue ID for the query
                if (! $this->currentVenueId) {
                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('Venue context lost. Cannot find schedule template.')
                        ->send();

                    continue; // Skip this booking
                }

                // Find the appropriate schedule template
                $newScheduleTemplate = ScheduleTemplate::findTemplateForDateTime(
                    $this->currentVenueId,
                    $newBookingAt,
                    $targetPartySize
                );

                if (! $newScheduleTemplate) {
                    Notification::make()
                        ->danger()
                        ->title('Update Failed')
                        ->body("No valid schedule template found for booking #{$booking->id} at the selected date/time.")
                        ->send();

                    continue; // Skip this booking
                }

                $newScheduleTemplateId = $newScheduleTemplate->id;

                // Prepare data for fill(), EXCLUDING booking_at initially
                $updateDataForFill = array_filter([
                    'guest_first_name' => $this->bulkEditData['guest_first_name'],
                    'guest_last_name' => $this->bulkEditData['guest_last_name'],
                    'guest_email' => $this->bulkEditData['guest_email'],
                    'guest_phone' => $this->bulkEditData['guest_phone'],
                    'guest_count' => $guestCount,
                    'status' => $this->bulkEditData['status'],
                    'schedule_template_id' => $newScheduleTemplateId,
                ]);

                // Handle booking updates: fill other data, set booking_at directly, then save
                $booking->fill($updateDataForFill);
                $booking->booking_at = $newBookingAt; // Set booking_at directly

                $saveResult = $booking->save();

                if (! $saveResult) {
                    Notification::make()
                        ->danger()
                        ->title('Update Failed')
                        ->body("Could not update booking #{$booking->id}. Save operation failed.")
                        ->send();

                    continue;
                }

                // Recalculation logic & Post-update actions (only if save was successful)
                $oldBookingAtCarbon = $oldBookingAt ? Carbon::parse($oldBookingAt) : null;
                if ($guestCount !== $oldGuestCount ||
                    ! $newBookingAt->equalTo($oldBookingAtCarbon) ||
                    $newScheduleTemplateId !== $oldScheduleTemplateId
                ) {
                    // Delete existing earnings
                    $booking->earnings()->delete();
                    // Recalculate total fee
                    $booking->total_fee = $booking->totalFee();
                    $booking->saveQuietly();
                    // Recalculate earnings
                    $calculationService->calculateEarnings($booking);
                }

                // Handle special status changes
                if ($this->bulkEditData['status'] !== $oldStatus) {
                    if ($this->bulkEditData['status'] === BookingStatus::CANCELLED->value || $this->bulkEditData['status'] === BookingStatus::NO_SHOW->value) {
                        $booking->earnings()->delete();
                    } elseif ($this->bulkEditData['status'] === BookingStatus::CONFIRMED->value) {
                        $booking->earnings()->update(['confirmed_at' => now()]);
                    }

                    // Dispatch BookingCancelled event for cancelled bookings only
                    if ($this->bulkEditData['status'] === BookingStatus::CANCELLED->value) {
                        BookingCancelled::dispatch($booking);
                    }
                }

                // Send confirmation if requested
                if ($this->bulkEditData['send_confirmation'] && in_array($booking->status, [
                    BookingStatus::CONFIRMED->value,
                    BookingStatus::VENUE_CONFIRMED->value,
                ])) {
                    $this->sendBookingConfirmation($booking);
                }

                // Log the activity
                activity()
                    ->performedOn($booking)
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => $this->bulkEditData['status'],
                        'old_guest_count' => $oldGuestCount,
                        'new_guest_count' => $guestCount,
                        'old_booking_at' => $oldBookingAt ? Carbon::parse($oldBookingAt)->toIso8601String() : null,
                        'new_booking_at' => $newBookingAt->toIso8601String(),
                        'old_schedule_template_id' => $oldScheduleTemplateId,
                        'new_schedule_template_id' => $newScheduleTemplateId,
                        'modified_by' => auth()->user()->name,
                        'bulk_edit' => true,
                        'confirmation_sent' => $this->bulkEditData['send_confirmation'] ?? false,
                    ])
                    ->log('Booking bulk edited');

                $editedCount++;
            }

            DB::commit();

            if ($editedCount > 0) {
                Notification::make()
                    ->success()
                    ->title('Bookings Updated')
                    ->body("Successfully updated {$editedCount} bookings.")
                    ->send();

                $this->clearEditForm();

                // Refresh the bookings list
                $this->dispatch('refresh');
            }

        } catch (Exception $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title('Error Updating Bookings')
                ->body('An error occurred: '.$e->getMessage())
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Venue $record) => ViewVenue::getUrl(['record' => $record]))
            ->query(
                Venue::query()
                    ->with(['partnerReferral.user.partner', 'user.authentications', 'venueGroup'])
                    ->withCount(['confirmedBookings'])
                    ->leftJoin('users', 'venues.user_id', '=', 'users.id')
                    ->when($this->onlyWithBookings, fn ($query) => $query->has('confirmedBookings'))
                    ->when($this->onlyActiveStatus,
                        fn ($query) => $query->where('venues.status', VenueStatus::ACTIVE->value))
                    ->when($this->region, fn ($query) => $query->where('venues.region', $this->region))
                    ->orderByDesc('users.updated_at')
            )
            ->headerActions([
                Action::make('bulkEdit')
                    ->label('Bulk Edit Venues')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url('/platform/bulk-edit-venues')
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
                ExportAction::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->exports([
                        ExcelExport::make('venues')
                            ->fromTable()
                            ->withWriterType(Excel::CSV)
                            ->withFilename('Venues-Export-'.now()->format('Y-m-d')),
                    ]),
            ])
            ->columns([
                TextColumn::make('tier')
                    ->label('Tier')
                    ->formatStateUsing(function (?int $state, Venue $record): string {
                        // Check if venue is in tier 1 (either DB tier=1 OR in config tier_1)
                        $goldVenues = ReservationService::getVenuesInTier($record->region, 1);
                        $silverVenues = ReservationService::getVenuesInTier($record->region, 2);

                        if ($record->tier === 1 || in_array($record->id, $goldVenues)) {
                            return 'Gold';
                        } elseif ($record->tier === 2 || in_array($record->id, $silverVenues)) {
                            return 'Silver';
                        } else {
                            return 'Standard';
                        }
                    })
                    ->badge()
                    ->color(function (?int $state, Venue $record): string {
                        // Check if venue is in tier 1 (either DB tier=1 OR in config tier_1)
                        $goldVenues = ReservationService::getVenuesInTier($record->region, 1);
                        $silverVenues = ReservationService::getVenuesInTier($record->region, 2);

                        if ($record->tier === 1 || in_array($record->id, $goldVenues)) {
                            return 'warning'; // Gold
                        } elseif ($record->tier === 2 || in_array($record->id, $silverVenues)) {
                            return 'gray';    // Silver
                        } else {
                            return 'primary'; // Standard (blue)
                        }
                    })
                    ->default(0)
                    ->size('xs'),
                TextColumn::make('name')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('region')
                    ->formatStateUsing(fn ($state) => Region::query()->find($state)?->name ?? '-')
                    ->label('Region')
                    ->grow(false)
                    ->size('xs')
                    ->visibleFrom('lg'),
                TextColumn::make('venueGroup.name')
                    ->label('Venue Group')
                    ->grow(false)
                    ->size('xs')
                    ->default('-')
                    ->formatStateUsing(fn (
                        $state,
                        Venue $record
                    ) => $record->venueGroup ? $record->venueGroup->name : '-')
                    ->visibleFrom('md')
                    ->searchable(),
                TextColumn::make('partnerReferral.user.name')->label('Partner')
                    ->url(fn (Venue $record) => $record->partnerReferral?->user?->partner
                        ? ViewPartner::getUrl(['record' => $record->partnerReferral->user->partner])
                        : null)
                    ->grow(false)
                    ->size('xs')
                    ->visibleFrom('sm'),
                TextColumn::make('id')->label('Earned')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(fn (Venue $venue) => $venue->formatted_total_earnings_in_u_s_d),
                TextColumn::make('confirmed_bookings_count')
                    ->label('Bookings')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->numeric(),
                TextColumn::make('user.authentications.login_at')
                    ->label('Last Login')
                    ->visibleFrom('sm')
                    ->grow(false)
                    ->size('xs')
                    ->formatStateUsing(function (Venue $record) {
                        $lastLogin = $record->user?->authentications()?->orderByDesc('login_at')->first();
                        if ($lastLogin && $lastLogin->login_at) {
                            return Carbon::parse($lastLogin->login_at, auth()->user()->timezone)->diffForHumans();
                        }

                        return 'Never';
                    })
                    ->default('Never'),
                TextColumn::make('user.secured_at')
                    ->label('Date Joined')
                    ->size('xs')
                    ->formatStateUsing(function ($state) {
                        $date = Carbon::parse($state);

                        return $date->isCurrentYear()
                            ? $date->timezone(auth()->user()->timezone)->format('M j, g:ia')
                            : $date->timezone(auth()->user()->timezone)->format('M j, Y g:ia');
                    })
                    ->sortable()
                    ->hidden(),
            ])
            ->filters([
                Filter::make('only_with_bookings')
                    ->label('Only venues with bookings')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->has('confirmedBookings')),

                Filter::make('only_active_status')
                    ->label('Only active status venues')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('venues.status', VenueStatus::ACTIVE->value)),

                SelectFilter::make('region')
                    ->label('Region')
                    ->options(fn () => Region::all()->pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data): Builder => $data['value'] ? $query->where('venues.region',
                        $data['value']) : $query
                    )
                    ->preload(),
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Venue $record) => $this->impersonateVenue($record->user, $record))
                    ->hidden(fn () => isPrimaApp()),
                EditAction::make()
                    ->iconButton()
                    ->extraAttributes([
                        'class' => 'hidden md:inline-flex',
                    ]),
                Action::make('bulkEditBookings')
                    ->iconButton()
                    ->icon('gmdi-menu-book')
                    ->label('Bulk Edit Bookings')
                    ->color('primary')
                    ->visible(fn () => in_array(auth()->id(), config('app.god_ids')))
                    ->action(function (Venue $record): void {
                        // Store venue ID and Name
                        $this->currentVenueId = $record->id;
                        $this->currentVenueName = $record->name;

                        // Reset selections and form state
                        $this->selectedBookings = [];
                        $this->showEditForm = false;
                        $this->bulkEditData = [
                            'guest_first_name' => '',
                            'guest_last_name' => '',
                            'guest_email' => '',
                            'guest_phone' => '',
                            'guest_count' => 1,
                            'booking_date' => null,
                            'booking_time' => null,
                            'status' => '',
                            'send_confirmation' => false,
                        ];

                        // Dispatch event to open modal
                        $this->dispatch('open-bulk-edit-modal', [
                            'venue_id' => $record->id,
                        ]);
                    }),
                Action::make('viewConcierge')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading(fn (Venue $concierge) => $concierge->user->name)
                    ->registerModalActions([
                        EditAction::make('edit')
                            ->size('sm'),
                        ViewAction::make('view')
                            ->size('sm'),
                    ])
                    ->modalContent(function (Venue $venue) {
                        try {
                            // Use Eloquent model with proper relationships instead of DB::table
                            $recentBookings = Booking::query()
                                ->whereHas('schedule', function (Builder $query) use ($venue) {
                                    $query->where('venue_id', $venue->id);
                                })
                                ->whereIn('status', [
                                    BookingStatus::CONFIRMED->value,
                                    BookingStatus::VENUE_CONFIRMED->value,
                                ])
                                ->with(['concierge.user'])
                                ->orderByDesc('created_at')
                                ->limit(10)
                                ->get();

                            $lastLogging = $venue->user->authentications()->latest('login_at')->first()->login_at ?? null;
                        } catch (Exception $e) {
                            // Log the error but continue
                            session()->put('concierge_modal_error', $e->getMessage());
                            $recentBookings = collect();
                            $lastLogging = null;
                        }

                        return view('partials.venue-table-modal-view', [
                            'user' => $venue->user,
                            'secured_at' => $venue->user->secured_at,
                            'referrer_name' => $venue->user->referrer?->name ?? '-',
                            'bookings_count' => number_format($venue->confirmed_bookings_count),
                            'earningsInUSD' => $venue->formattedTotalEarningsInUSD,
                            'recentBookings' => $recentBookings,
                            'last_login' => $lastLogging,
                            'contacts' => $venue->contacts,
                        ]);
                    })
                    ->modalContentFooter(fn (Action $action) => view(
                        'partials.modal-actions-footer',
                        ['action' => $action]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public function populateEditFormFromBooking($bookingId = null)
    {
        if (blank($bookingId)) {
            Notification::make()
                ->warning()
                ->title('No Booking Selected')
                ->body('Please select a booking to edit.')
                ->send();

            return;
        }

        $booking = Booking::query()->find($bookingId);

        if (! $booking) {
            Notification::make()
                ->error()
                ->title('Booking Not Found')
                ->body('The selected booking could not be found.')
                ->send();

            return;
        }

        // Keep the venue ID and name
        // Don't reset the venue information when selecting a booking

        // Format the booking date properly for datetime-local input
        $bookingAtFormatted = $booking->booking_at ?
            Carbon::parse($booking->booking_at)->format('Y-m-d\TH:i') :
            null;

        // Populate the bulk edit form with the booking data
        $this->bulkEditData = [
            'guest_first_name' => $booking->guest_first_name,
            'guest_last_name' => $booking->guest_last_name,
            'guest_email' => $booking->guest_email,
            'guest_phone' => $booking->guest_phone,
            'guest_count' => $booking->guest_count,
            'booking_date' => $booking->booking_at ? Carbon::parse($booking->booking_at)->format('Y-m-d') : null,
            'booking_time' => $booking->booking_at ? Carbon::parse($booking->booking_at)->format('H:i') : null,
            'status' => $booking->status,
            'send_confirmation' => false,
        ];

        // Clear any previous selections and select only this booking
        $this->selectedBookings = [$bookingId];

        // Show the edit form
        $this->showEditForm = true;
    }

    /**
     * Handle the date range filter form submission
     */
    public function filterBookings()
    {
        // No additional logic needed - the startDate and endDate properties
        // are automatically updated by Livewire's wire:model binding
        // The computed filteredBookings property will refresh automatically

        // Force a refresh of the computed property
        $this->dispatch('refresh');
    }

    /**
     * Reset the filters
     */
    public function resetFilter(): void
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->customerSearch = null;
        $this->selectedBookings = [];
        $this->shouldSelectAllBookings = false;
        $this->showEditForm = false;
        $this->onlyWithBookings = false;
        $this->onlyActiveStatus = false;
        $this->region = null;

        // Refresh the listing
        $this->dispatch('refresh');
    }

    public function editSelectedBookings()
    {
        if (blank($this->selectedBookings)) {
            Notification::make()
                ->warning()
                ->title('No Bookings Selected')
                ->body('Please select at least one booking to edit.')
                ->send();

            return;
        }

        // Validate guest count before proceeding
        $guestCount = (int) $this->bulkEditData['guest_count'];
        if ($guestCount < $this->getMinGuestCount() || $guestCount > $this->getMaxGuestCount()) {
            Notification::make()
                ->danger()
                ->title('Invalid Party Size')
                ->body("Party size must be between {$this->getMinGuestCount()} and {$this->getMaxGuestCount()}.")
                ->send();

            return;
        }

        $email = $this->bulkEditData['guest_email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->danger()
                ->title('Invalid Email')
                ->body('Please enter a valid email address.')
                ->send();
            return;
        }

        $this->bulkEditBookings();

        // Reset selection after update
        $this->selectedBookings = [];

        // Hide the edit form
        $this->showEditForm = false;

        // Clear the modal flag
        session()->forget('bulk_edit_modal_open');
    }

    /**
     * Handle modal closed event to clear session flags
     */
    public function clearModalFlags(bool $clearVenueContext = true): void
    {
        if ($clearVenueContext) {
            $this->currentVenueId = null;
            $this->currentVenueName = null;
        }

        $this->selectedBookings = [];
        $this->shouldSelectAllBookings = false;
        $this->showEditForm = false;
        $this->bulkEditData = [
            'guest_first_name' => '',
            'guest_last_name' => '',
            'guest_email' => '',
            'guest_phone' => '',
            'guest_count' => $this->getMinGuestCount(),
            'booking_date' => null,
            'booking_time' => null,
            'status' => '',
            'send_confirmation' => false,
        ];
    }

    public function mount(): void
    {
        parent::mount();
        $this->clearModalFlags();

        // Set default date range to the last 30 days
        $userTimezone = auth()->user()?->timezone ?? config('app.default_timezone');
        $this->endDate = Carbon::now($userTimezone)->format('Y-m-d');
        $this->startDate = Carbon::now($userTimezone)->subDays(30)->format('Y-m-d');
    }

    /**
     * Helper method to get filtered bookings for use in methods like toggleAllBookings
     */
    public function getFilteredBookings()
    {
        return $this->filteredBookings();
    }

    public function toggleAllBookings(): void
    {
        $this->shouldSelectAllBookings = ! $this->shouldSelectAllBookings;

        // Either we can select everything or reset the selection
        if ($this->shouldSelectAllBookings) {
            $this->selectedBookings = $this->getFilteredBookings()->pluck('id')->flip()->map(fn () => true)->toArray();
        } else {
            $this->selectedBookings = [];
        }
    }

    public function toggleEditForm(bool $state): void
    {
        $this->showEditForm = $state;
    }

    /**
     * Clear the edit form and reset all related state
     */
    public function clearEditForm(): void
    {
        // Clear form state but preserve venue context
        $this->clearModalFlags(false);

        $this->dispatch('bulk-edit-form-cleared');
    }

    /**
     * Handle when the edit modal is closed
     */
    #[On('close-modal')]
    public function handleModalClosed($data = []): void
    {
        // Only clear modal flags when the bulk-edit-bookings-modal is closed
        if (isset($data['id']) && $data['id'] === 'bulk-edit-bookings-modal') {
            $this->clearModalFlags(true); // Clear venue context when modal is actually closed
        }
    }

    /**
     * Process the bulk ID status update
     */
    public function bulkUpdateBookingStatuses(): void
    {
        if (blank($this->bulkIdsInput) || blank($this->bulkStatus)) {
            Notification::make()
                ->warning()
                ->title('Missing Information')
                ->body('Please provide both booking IDs and a status to update.')
                ->send();

            return;
        }

        // Parse IDs - supports comma-separated, space-separated, or one-per-line
        $ids = preg_split('/[\s,]+/', trim($this->bulkIdsInput));
        $ids = array_filter($ids); // Remove empty values

        if (blank($ids)) {
            Notification::make()
                ->warning()
                ->title('No IDs Found')
                ->body('Please provide valid booking IDs.')
                ->send();

            return;
        }

        // Validate the status
        $validStatuses = [
            BookingStatus::CANCELLED->value,
            BookingStatus::NO_SHOW->value,
        ];

        if (! in_array($this->bulkStatus, $validStatuses)) {
            Notification::make()
                ->warning()
                ->title('Invalid Status')
                ->body('Please select a valid status (Cancelled or No-Show).')
                ->send();

            return;
        }

        DB::beginTransaction();
        try {
            $bookings = Booking::query()->whereIn('id', $ids)->get();

            if ($bookings->isEmpty()) {
                DB::rollBack();
                Notification::make()
                    ->warning()
                    ->title('No Bookings Found')
                    ->body('None of the provided IDs matched existing bookings.')
                    ->send();

                return;
            }

            $updatedCount = 0;
            $calculationService = app(BookingCalculationService::class);

            foreach ($bookings as $booking) {
                $oldStatus = $booking->status;

                // Skip if status is already the same
                if ($oldStatus->value === $this->bulkStatus) {
                    continue;
                }

                $booking->status = $this->bulkStatus;
                $booking->save();

                // Remove earnings for cancelled and no-show bookings
                if ($this->bulkStatus === BookingStatus::CANCELLED->value || $this->bulkStatus === BookingStatus::NO_SHOW->value) {
                    $booking->earnings()->delete();
                }

                // Dispatch BookingCancelled event for cancelled bookings only
                if ($this->bulkStatus === BookingStatus::CANCELLED->value) {
                    BookingCancelled::dispatch($booking);
                }

                // Log the activity
                activity()
                    ->performedOn($booking)
                    ->withProperties([
                        'old_status' => $oldStatus,
                        'new_status' => $this->bulkStatus,
                        'modified_by' => auth()->user()->name,
                        'bulk_update' => true,
                    ])
                    ->log('Booking status bulk updated');

                $updatedCount++;
            }

            DB::commit();

            if ($updatedCount > 0) {
                Notification::make()
                    ->success()
                    ->title('Bookings Updated')
                    ->body("Successfully updated {$updatedCount} of ".count($ids).' bookings.')
                    ->send();

                // Reset form
                $this->bulkIdsInput = '';
                $this->bulkStatus = '';
            } else {
                Notification::make()
                    ->info()
                    ->title('No Changes')
                    ->body('No bookings were updated. They may already have the selected status.')
                    ->send();
            }

        } catch (Exception $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title('Error Updating Bookings')
                ->body('An error occurred: '.$e->getMessage())
                ->send();
        }
    }
}
