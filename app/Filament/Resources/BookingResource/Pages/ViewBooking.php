<?php

/** @noinspection PhpDynamicFieldDeclarationInspection
 * @noinspection UnknownInspectionInspection
 */

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * @property Booking $record
 */
class ViewBooking extends ViewRecord
{
    use FormatsPhoneNumber;

    protected static string $resource = BookingResource::class;

    protected static string $view = 'livewire.customer-invoice';

    public bool $download = false;

    public Booking $booking;

    public bool $showConcierges = false;

    public Region $region;

    public ?string $originalPreviousUrl = null;

    public function mount(string|int $record): void
    {
        $this->record = Booking::with('earnings.user')
            ->firstWhere('id', $record);

        abort_if(! in_array($this->record->status, [BookingStatus::CONFIRMED, BookingStatus::NO_SHOW], true), 404);

        if (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('partner') || auth()->user()->hasRole('concierge')) {
            $this->showConcierges = true;
        }

        $this->authorizeAccess();

        $this->booking = $this->record;
        $this->region = Region::query()->find($this->booking->city);

        // Store the original previous URL
        $this->originalPreviousUrl = URL::previous();
    }

    public function resendInvoice(): void
    {
        $this->booking->notify(new CustomerBookingConfirmed);

        Notification::make()
            ->title('Customer Invoice Resent')
            ->success()
            ->send();
    }

    public function resendInvoiceAction(): Action
    {
        return Action::make('resendInvoice')
            ->label('Resend Customer Invoice')
            ->color('indigo')
            ->icon('gmdi-message')
            ->requiresConfirmation()
            ->modalDescription(function () {
                $formattedNumber = $this->getFormattedPhoneNumber($this->record->guest_phone);

                return new HtmlString(
                    'Are you sure you want to resend the invoice?<br>'.
                    "<span class='block mt-2 text-lg font-bold'>$formattedNumber</span>"
                );
            })
            ->extraAttributes(['class' => 'w-full'])
            ->action(fn () => $this->resendInvoice());
    }

    public function deleteBookingAction(): Action
    {
        return Action::make('deleteBooking')
            ->label('Delete Booking')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalHeading('Delete Booking')
            ->extraAttributes(['class' => 'w-full'])
            ->modalDescription(fn () => new HtmlString(
                'Are you certain you want to delete this booking? This action is irreversible.<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md'>This action will be logged and cannot be undone.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                '<p><strong>Fee:</strong> '.money($this->record->total_fee, $this->record->currency).'</p>'.
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->modalSubmitActionLabel('Delete')
            ->modalCancelActionLabel('Cancel')
            ->action(fn () => $this->deleteBooking());
    }

    private function deleteBooking(): void
    {
        if (! auth()->user()->hasRole('super_admin')) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to delete bookings.')
                ->send();

            return;
        }

        if ($this->record->created_at->lt(Carbon::now()->subHours(24))) {
            Notification::make()
                ->danger()
                ->title('Cannot Delete')
                ->body('Bookings older than 24 hours cannot be deleted.')
                ->send();

            return;
        }

        $this->record->delete();

        Notification::make()
            ->success()
            ->title('Booking Deleted')
            ->body('The booking has been successfully deleted.')
            ->send();

        // Redirect to the original previous URL if it's valid
        if ($this->originalPreviousUrl) {
            $this->redirect($this->originalPreviousUrl);
        } else {
            // If no valid previous URL, redirect to the bookings index
            $this->redirect(BookingResource::getUrl());
        }
    }
}
