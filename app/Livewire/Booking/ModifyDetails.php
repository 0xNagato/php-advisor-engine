<?php

namespace App\Livewire\Booking;

use App\Actions\Booking\Authorization\CanModifyBooking;
use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Mail\CustomerInvoice;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\VenueBookingCancelled;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Component;

class ModifyDetails extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Booking $record;

    public Region $region;

    public bool $emailed = false;

    public bool $emailOpen = false;

    public bool $canModifyBooking = false;

    public string $email;

    public function mount(string $token): void
    {
        $this->record = Booking::query()->where('uuid', $token)
            ->with('earnings.user')
            ->firstOrFail();

        $this->region = Region::query()->find($this->record->city)
            ?? Region::query()->find($this->record->venue->region)
            ?? Region::default();
        $this->canModifyBooking = CanModifyBooking::run($this->record, auth()->user());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email Address')
                    ->prefixIcon('gmdi-mail-o')
                    ->email()
                    ->placeholder('Enter your email address')
                    ->hiddenLabel()
                    ->required(),
            ]);
    }

    public function render(): View
    {
        return view('livewire.booking.modify-details');
    }

    public function showEmailForm(): void
    {
        $this->emailOpen = ! $this->emailOpen;
    }

    public function emailInvoice(): void
    {
        $invoicePath = $this->record->invoice_path;

        $mailable = new CustomerInvoice($this->record);
        $mailable->attachFromStorageDisk('do', $invoicePath)
            ->from('welcome@primavip.co', 'PRIMA');

        Mail::to($this->email)
            ->send($mailable);
        $this->emailOpen = false;
        $this->emailed = true;

        Notification::make()
            ->title('Invoice sent to '.$this->email)
            ->success()
            ->send();
    }

    public function cancelBookingAction(): Action
    {
        $modalDescription = 'Are you sure you want to cancel this booking?<br><br>';
        if ($this->canModifyBooking) {
            $modalDescription = 'Do you want to change this reservation to another day?<br>
            Or proceed to Abandon Cancellation?';
        }

        return Action::make('cancelBooking')
            ->label('Cancel Booking')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->button()->size('lg')
            ->requiresConfirmation()
            ->modalWidth(MaxWidth::Medium)
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalHeading('Cancel Booking')
            ->modalDescription(fn () => new HtmlString($modalDescription))
            ->modalContent(fn (): View => view('partials.cancel-booking-action-modal'))
            ->extraAttributes(['class' => 'w-full'])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Abandon Cancellation')
            ->registerModalActions([
                Action::make('cancelThisBooking')
                    ->label('Cancel Booking')
                    ->color('danger')
                    ->size(ActionSize::ExtraLarge)
                    ->extraAttributes(['class' => 'w-full'])
                    ->action(function () {
                        $this->cancelNonPrimeBooking();

                        $redirectTo = URL::signedRoute('modify.booking', $this->record->uuid);

                        return redirect()->to($redirectTo);
                    }),
                $this->modifyBookingAction(null, false),
            ])
            ->modalContentFooter(fn (Action $action) => view(
                'partials.cancel-booking-action-footer-modal',
                ['canModifyBooking' => $this->canModifyBooking, 'action' => $action]
            ))
            ->visible(function () {
                // Non-cancellable statuses for everyone
                $nonCancellableStatuses = [
                    BookingStatus::CANCELLED,
                    BookingStatus::REFUNDED,
                    BookingStatus::PARTIALLY_REFUNDED,
                    BookingStatus::ABANDONED,
                ];

                if (in_array($this->record->status, $nonCancellableStatuses)) {
                    return false;
                }

                // Cannot cancel prime bookings
                if ($this->record->is_prime) {
                    return false;
                }

                // Regular users need to use canModifyBooking
                return $this->canModifyBooking;
            })
            ->disabled(fn () => $this->record->status === BookingStatus::CANCELLED);
    }

    public function modifyBookingAction($label = null, $icon = true): Action
    {
        $label ??= $this->record->hasActiveModificationRequest() ? 'Modification Request Pending' : 'Modify Booking';

        return Action::make('modifyBooking')
            ->label($label)
            ->icon($icon ? 'heroicon-m-pencil-square' : null)
            ->button()->size('lg')
            ->requiresConfirmation()
            ->modalWidth(MaxWidth::Medium)
            ->modalHeading('Modify Booking')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('indigo')
            ->modalDescription(fn () => new HtmlString(
                '<div class="text-sm text-gray-500">'.
                'We must confirm all reservations with the participating venue. '.
                'Please submit any change requests needed here. '.
                'We will confirm the changes requested within 15-30 minutes and notify both '.
                'you and the customer.</div>'
            ))
            ->modalContent(view('partials.modify-booking-action-modal', [
                'booking' => $this->record,
            ]))
            ->modalWidth('md')
            ->visible(isset($this->canModifyBooking) && $this->canModifyBooking && $this->record->status !== BookingStatus::CANCELLED)
            ->disabled($this->record->hasActiveModificationRequest())
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->extraAttributes(['class' => 'w-full']);
    }

    private function cancelNonPrimeBooking(): void
    {
        // For non-super admins, check if booking time has passed
        $bookingTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->record->booking_at,
            $this->record->venue->timezone
        );
        $now = now($this->record->venue->timezone);

        // Cannot cancel within 30 minutes before booking or after booking has started
        $pastTimeCheck = $now->diffInMinutes(
            $bookingTime,
            false
        ) > CanModifyBooking::MINUTES_BEFORE_BOOKING_TO_MODIFY
            && $now <= $bookingTime;

        // Check permissions
        if (! $this->canModifyBooking || ! $pastTimeCheck) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You cannot cancel within 30 minutes before booking or after booking has started.')
                ->send();

            return;
        }

        // Check for canceled status regardless of user role
        if ($this->record->status === BookingStatus::CANCELLED) {
            Notification::make()
                ->danger()
                ->title('Cannot Cancel Booking')
                ->body('This booking is already cancelled.')
                ->send();

            return;
        }

        if ($this->record->is_prime) {
            Notification::make()
                ->danger()
                ->title('Invalid Action')
                ->body('Cannot cancel a prime booking.')
                ->send();

            return;
        }

        $this->record->update([
            'status' => BookingStatus::CANCELLED,
        ]);

        // Dispatch BookingCancelled event for platform sync
        BookingCancelled::dispatch($this->record);

        // Validate if the booking is older than created + 5 minutes
        if ($this->record->created_at->diffInMinutes(now()) > 5) {
            // Send cancellation notification to venue contacts
            $this->record->venue->contacts->each(function ($contact) {
                $contact->notify(new VenueBookingCancelled($this->record));
            });
        }

        // Delete any existing earnings
        $this->record->earnings()->delete();

        activity()
            ->performedOn($this->record)
            ->withProperties([
                'guest_name' => $this->record->guest_name,
                'venue_name' => $this->record->venue->name,
                'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                'guest_count' => $this->record->guest_count,
            ])
            ->log('Non-prime booking cancelled');

        Notification::make()
            ->success()
            ->title('Booking Cancelled')
            ->body('The booking has been successfully cancelled.')
            ->send();
    }
}
