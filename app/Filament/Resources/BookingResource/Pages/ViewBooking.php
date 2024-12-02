<?php

/** @noinspection PhpDynamicFieldDeclarationInspection
 * @noinspection UnknownInspectionInspection
 */

namespace App\Filament\Resources\BookingResource\Pages;

use App\Actions\Booking\RefundBooking;
use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
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

    public ?int $refundAmount = null;

    public function mount(string|int $record): void
    {
        $this->record = Booking::with('earnings.user')
            ->firstWhere('id', $record);

        abort_if(! in_array($this->record->status, [
            BookingStatus::CONFIRMED,
            BookingStatus::NO_SHOW,
            BookingStatus::REFUNDED,
            BookingStatus::PARTIALLY_REFUNDED,
        ], true), 404);

        if (auth()->user()->hasActiveRole('super_admin') || auth()->user()->hasActiveRole('partner') || auth()->user()->hasActiveRole('concierge')) {
            $this->showConcierges = true;
        }

        $this->authorizeAccess();

        $this->booking = $this->record;
        $this->region = Region::query()->find($this->booking->city);

        // Store the original previous URL
        $this->originalPreviousUrl = URL::previous();

        $this->refundAmount = $this->record->total_with_tax_in_cents;
    }

    public function resendInvoice(): void
    {
        $this->booking->notify(new CustomerBookingConfirmed);

        activity()
            ->performedOn($this->record)
            ->withProperties([
                'guest_name' => $this->record->guest_name,
                'guest_phone' => $this->record->guest_phone,
                'guest_email' => $this->record->guest_email,
                'amount' => $this->record->total_with_tax_in_cents,
                'currency' => $this->record->currency,
            ])
            ->log('Invoice resent to customer');

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
            ->modalDescription(fn (Get $get) => new HtmlString(
                'Are you sure you want to resend the invoice?<br>'.
                "<span class='block mt-2 text-lg font-bold'>{$this->getFormattedPhoneNumber($this->record->guest_phone)}</span>"
            ))
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
            ->modalDescription(fn (Get $get) => new HtmlString(
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
            ->hidden(fn () => auth()->id() !== 1)
            ->action(fn () => $this->deleteBooking());
    }

    private function deleteBooking(): void
    {
        if (auth()->id() !== 1) {
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

        activity()
            ->performedOn($this->record)
            ->withProperties([
                'guest_name' => $this->record->guest_name,
                'venue_name' => $this->record->venue->name,
                'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                'guest_count' => $this->record->guest_count,
                'amount' => $this->record->total_with_tax_in_cents,
                'currency' => $this->record->currency,
            ])
            ->log('Booking deleted');

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

    public function refundBookingAction(): Action
    {
        return Action::make('refundBooking')
            ->label('Process Refund')
            ->color('danger')
            ->icon('gmdi-money')
            ->form([
                Select::make('refund_type')
                    ->label('Refund Type')
                    ->required()
                    ->options([
                        'full' => 'Full Refund',
                        'partial' => 'Partial Refund (By Guest Count)',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, $get) {
                        $this->refundAmount = $this->calculateRefundAmount($state, $get('guest_count'));
                    })
                    ->default('full'),

                Select::make('guest_count')
                    ->label('Number of Guests to Refund')
                    ->options(function () {
                        $guestCount = $this->record->guest_count;

                        return collect(range(1, $guestCount))
                            ->mapWithKeys(fn ($count) => [$count => "{$count} Guest(s)"])
                            ->toArray();
                    })
                    ->visible(fn (Get $get) => $get('refund_type') === 'partial')
                    ->required(fn (Get $get) => $get('refund_type') === 'partial')
                    ->live()
                    ->afterStateUpdated(function ($state, $get) {
                        $this->refundAmount = $this->calculateRefundAmount($get('refund_type'), $state);
                    }),

                Select::make('stripe_reason')
                    ->label('Stripe Reason')
                    ->required()
                    ->options([
                        'duplicate' => 'Duplicate Charge',
                        'fraudulent' => 'Fraudulent',
                        'requested_by_customer' => 'Requested by Customer',
                    ])
                    ->placeholder('Select a reason for Stripe'),

                Textarea::make('refund_reason')
                    ->label('Internal Notes')
                    ->required()
                    ->placeholder('Please provide detailed internal notes about this refund')
                    ->maxLength(255),
            ])
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalHeading('Process Refund')
            ->modalDescription(fn () => new HtmlString(
                'Are you sure you want to process a refund for this booking?<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md'>This action will be logged and cannot be undone.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                '<p><strong>Amount to be refunded:</strong> '.
                money($this->refundAmount, $this->record->currency).
                '</p>'.
                '</div>'
            ))
            ->modalSubmitActionLabel('Refund')
            ->modalCancelActionLabel('Cancel')
            ->disabled(fn () => $this->record->status === BookingStatus::REFUNDED)
            ->extraAttributes(['class' => 'w-full'])
            ->action(function (array $data) {
                $this->processRefund(
                    $data['stripe_reason'],
                    $data['refund_reason'],
                    $data['refund_type'],
                    $data['guest_count'] ?? null
                );
            });
    }

    private function processRefund(string $stripeReason, string $internalReason, string $refundType, ?int $guestCount): void
    {
        if (! auth()->user()->hasActiveRole('super_admin')) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to process refunds.')
                ->send();

            return;
        }

        $refundAmount = $this->calculateRefundAmount($refundType, $guestCount);
        $result = RefundBooking::run($this->record, $stripeReason, $refundAmount);

        if ($result['success']) {
            $this->record->update([
                'refund_reason' => $internalReason,
                'refunded_guest_count' => $guestCount,
                'status' => $refundType === 'full' ? BookingStatus::REFUNDED : BookingStatus::PARTIALLY_REFUNDED,
            ]);

            activity()
                ->performedOn($this->record)
                ->withProperties([
                    'guest_name' => $this->record->guest_name,
                    'venue_name' => $this->record->venue->name,
                    'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                    'amount_refunded' => $refundAmount,
                    'currency' => $this->record->currency,
                    'refund_id' => $result['refund_id'] ?? null,
                    'stripe_reason' => $stripeReason,
                    'internal_reason' => $internalReason,
                    'refund_type' => $refundType,
                    'refunded_guest_count' => $guestCount,
                ])
                ->log('Booking refunded');

            Notification::make()
                ->success()
                ->title('Refund Processed')
                ->body('The refund has been successfully processed.')
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        } else {
            Notification::make()
                ->danger()
                ->title('Refund Failed')
                ->body($result['message'])
                ->send();
        }
    }

    private function calculateRefundAmount(string $refundType, ?int $guestCount): int
    {
        if ($refundType === 'full' || ! $guestCount || $guestCount === $this->record->guest_count) {
            return $this->record->total_with_tax_in_cents;
        }

        // Calculate per guest amount and ensure we get exact division
        $perGuestAmount = (int) ($this->record->total_with_tax_in_cents / $this->record->guest_count);

        return $perGuestAmount * $guestCount;
    }
}
