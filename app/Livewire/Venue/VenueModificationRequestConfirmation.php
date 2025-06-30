<?php

namespace App\Livewire\Venue;

use App\Models\BookingModificationRequest;
use App\Services\BookingModificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class VenueModificationRequestConfirmation extends Page
{
    public BookingModificationRequest $modificationRequest;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.venue.venue-modification-request-confirmation';

    public function mount(BookingModificationRequest $modificationRequest): void
    {
        $this->modificationRequest = $modificationRequest->load('booking');
    }

    /**
     * @throws Throwable
     */
    public function approveModification(): void
    {
        app(BookingModificationService::class)
            ->approve($this->modificationRequest);

        Notification::make()
            ->success()
            ->title('Modification Request Approved')
            ->body('The booking has been updated and all parties will be notified.')
            ->send();
    }

    public function rejectModification(?string $reason = null): void
    {
        app(BookingModificationService::class)
            ->reject($this->modificationRequest, $reason);

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
