<?php

/** @noinspection PhpDynamicFieldDeclarationInspection */

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\GuestBookingConfirmed;
use App\Traits\FormatsPhoneNumber;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
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
    }

    public function resendInvoice(): void
    {
        $this->booking->notify(new GuestBookingConfirmed);

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
                    "<span class='block mt-2 text-lg font-bold'>{$formattedNumber}</span>"
                );
            })
            ->extraAttributes(['class' => 'w-full mt-4'])
            ->action(fn () => $this->resendInvoice());
    }
}
