<?php

namespace App\Livewire\Venue;

use App\Models\BookingModificationRequest;
use App\Notifications\Booking\ConciergeModificationApproved;
use App\Notifications\Booking\ConciergeModificationRejected;
use App\Notifications\Booking\CustomerModificationApproved;
use App\Notifications\Booking\CustomerModificationRejected;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class VenueModificationRequestConfirmation extends Page
{
    public BookingModificationRequest $modificationRequest;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.venue.venue-modification-request-confirmation';

    public function mount(BookingModificationRequest $modificationRequest): void
    {
        $this->modificationRequest = $modificationRequest->load('booking');
    }

    public function approveModification(): void
    {
        $this->modificationRequest->markAsApproved();

        // Update the booking with new details using DB facade
        DB::table('bookings')
            ->where('id', $this->modificationRequest->booking->id)
            ->update([
                'guest_count' => $this->modificationRequest->requested_guest_count,
                'schedule_template_id' => $this->modificationRequest->requested_schedule_template_id,
                'booking_at' => $this->modificationRequest->booking->booking_at->format('Y-m-d').' '.
                    $this->modificationRequest->requested_time,
            ]);

        // Send notifications
        $this->modificationRequest->notify(new CustomerModificationApproved);
        $this->modificationRequest->notify(new ConciergeModificationApproved);

        activity()
            ->performedOn($this->modificationRequest->booking)
            ->withProperties([
                'modification_request_id' => $this->modificationRequest->id,
                'status' => 'approved',
            ])
            ->log('Venue approved booking modification request');

        Notification::make()
            ->success()
            ->title('Modification Request Approved')
            ->body('The booking has been updated and all parties will be notified.')
            ->send();
    }

    public function rejectModification(?string $reason = null): void
    {
        $this->modificationRequest->markAsRejected($reason);

        // Send notifications
        $this->modificationRequest->notify(new CustomerModificationRejected);
        $this->modificationRequest->notify(new ConciergeModificationRejected);

        activity()
            ->performedOn($this->modificationRequest->booking)
            ->withProperties([
                'modification_request_id' => $this->modificationRequest->id,
                'status' => 'rejected',
                'reason' => $reason,
            ])
            ->log('Venue rejected booking modification request');

        Notification::make()
            ->success()
            ->title('Modification Request Rejected')
            ->body('All parties will be notified of the rejection.')
            ->send();
    }

    public function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve Changes')
            ->color('success')
            ->button()
            ->requiresConfirmation()
            ->modalHeading('Approve Modification Request')
            ->modalDescription('Are you sure you want to approve these changes?')
            ->action(fn () => $this->approveModification());
    }

    public function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject Changes')
            ->color('gray')
            ->outlined()
            ->button()
            ->form([
                Textarea::make('reason')
                    ->label('Rejection Reason')
                    ->required(),
            ])
            ->action(fn (array $data) => $this->rejectModification($data['reason']));
    }
}
