<?php

/**
 * @noinspection PhpDynamicFieldDeclarationInspection
 * @noinspection UnknownInspectionInspection
 */

namespace App\Filament\Resources\BookingResource\Pages;

use App\Actions\Booking\Authorization\CanModifyBooking;
use App\Actions\Booking\ConvertToNonPrime;
use App\Actions\Booking\ConvertToPrime;
use App\Actions\Booking\RefundBooking;
use App\Enums\BookingStatus;
use App\Enums\EarningType;
use App\Events\BookingMarkedAsNoShow;
use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Earning;
use App\Models\Region;
use App\Notifications\Booking\CustomerBookingConfirmed;
use App\Notifications\Booking\VenueBookingCancelled;
use App\Services\Booking\BookingCalculationService;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

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

    protected $listeners = ['booking-modified' => '$refresh'];

    public bool $canModifyBooking = false;

    public function mount(string|int $record): void
    {
        $this->record = Booking::with('earnings.user')
            ->firstWhere('id', $record);

        if (auth()->user()->hasActiveRole('super_admin') || auth()->user()->hasActiveRole('partner') || auth()->user()->hasActiveRole('concierge')) {
            $this->showConcierges = true;
        }

        $this->authorizeAccess();

        $this->booking = $this->record;
        $this->region = Region::query()->find($this->booking->venue->region)->first();

        // Store the original previous URL
        $this->originalPreviousUrl = URL::previous();

        $this->refundAmount = $this->record->total_with_tax_in_cents;
        $this->canModifyBooking = CanModifyBooking::run($this->record, auth()->user());
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
            ->color('primary')
            ->icon('gmdi-message')
            ->requiresConfirmation()
            ->modalDescription(fn (Get $get) => new HtmlString(
                'Are you sure you want to resend the invoice?<br>'.
                "<span class='block mt-2 text-lg font-bold'>{$this->getFormattedPhoneNumber($this->record->guest_phone)}</span>"
            ))
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->hidden(fn () => ! in_array($this->record->status, [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
                BookingStatus::REFUNDED,
                BookingStatus::PARTIALLY_REFUNDED,
            ]))
            ->action(fn () => $this->resendInvoice());
    }

    public function refundBookingAction(): Action
    {
        if (! $this->record->is_prime
            || $this->record->is_refunded_or_partially_refunded
            || ! in_array($this->record->status, [
                BookingStatus::CONFIRMED,
                BookingStatus::VENUE_CONFIRMED,
            ])
        ) {
            return Action::make('refundBooking')->hidden();
        }

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
                        $this->refundAmount = app(BookingCalculationService::class)
                            ->calculateRefundAmount($this->record, $state, $get('guest_count'));
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
                        $this->refundAmount = app(BookingCalculationService::class)
                            ->calculateRefundAmount($this->record, $get('refund_type'), $state);
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
            ->disabled(fn () => in_array(
                $this->record->status,
                [BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED]
            ))
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

    private function processRefund(
        string $stripeReason,
        string $internalReason,
        string $refundType,
        ?int $guestCount
    ): void {
        $refundAmount = app(BookingCalculationService::class)->calculateRefundAmount(
            $this->record,
            $refundType,
            $guestCount
        );
        $result = RefundBooking::run($this->record, $stripeReason, $refundAmount);

        if ($result['success']) {
            // Calculate refund percentage for partial refunds
            $refundPercentage = $refundType === 'full' ? 1 : $guestCount / $this->record->guest_count;

            // Create refund earnings for each original earning
            foreach ($this->record->earnings as $earning) {
                $earningRefundAmount = (int) ($earning->amount * $refundPercentage);

                if ($earningRefundAmount > 0) {
                    Earning::query()->create([
                        'user_id' => $earning->user_id,
                        'booking_id' => $this->record->id,
                        'type' => $earning->type,
                        'amount' => -$earningRefundAmount,
                        'currency' => $earning->currency,
                        'percentage' => $earning->percentage,
                        'percentage_of' => EarningType::REFUND->value,
                        'confirmed_at' => now(),
                    ]);
                }
            }

            $this->record->update([
                'refund_reason' => $internalReason,
                'refunded_guest_count' => $guestCount,
                'total_refunded' => $refundAmount,
                'platform_earnings_refunded' => (int) ($this->record->platform_earnings * $refundPercentage),
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
        } else {
            Notification::make()
                ->danger()
                ->title('Refund Failed')
                ->body($result['message'])
                ->send();
        }
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
            ->modalContent(fn (Action $action): View => view(
                'partials.cancel-booking-action-modal',
                ['action' => $action, 'canModifyBooking' => $this->canModifyBooking]
            ))
            ->extraAttributes(['class' => 'w-full'])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Abandon Cancellation')
            ->registerModalActions([
                Action::make('cancelThisBooking')
                    ->label('Cancel Booking')
                    ->color('danger')
                    ->size(ActionSize::ExtraLarge)
                    ->extraAttributes(['class' => 'w-full'])
                    ->action(fn () => $this->cancelNonPrimeBooking()),
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

                // Super admin can cancel any non-prime booking that's not in a non-cancellable status
                if (auth()->user()->hasActiveRole('super_admin')) {
                    return true;
                }

                // Regular users need to use canModifyBooking
                return $this->canModifyBooking;
            })
            ->disabled(fn () => $this->record->status === BookingStatus::CANCELLED);
    }

    private function cancelNonPrimeBooking(): void
    {
        // Only super admins can cancel past the booking time
        $isSuperAdmin = auth()->user()->hasActiveRole('super_admin');
        $pastTimeCheck = true;

        if (! $isSuperAdmin) {
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
        }

        // Check permissions
        if ((! $isSuperAdmin && ! $this->canModifyBooking) || (! $isSuperAdmin && ! $pastTimeCheck)) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to cancel this booking.')
                ->send();

            return;
        }

        // Check for cancelled status regardless of user role
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
                ->body('Cannot cancel a prime booking. Please use refund instead.')
                ->send();

            return;
        }

        $this->record->update([
            'status' => BookingStatus::CANCELLED,
        ]);

        // Send cancellation notification to venue contacts
        $this->record->venue->contacts->each(function ($contact) {
            $this->record->notify(new VenueBookingCancelled($contact));
        });

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

    public function convertToNonPrimeBookingAction(): Action
    {
        return Action::make('convertToNonPrimeBooking')
            ->label('Convert to Non Prime')
            ->color('warning')
            ->icon('gmdi-money-off-csred-o')
            ->requiresConfirmation()
            ->modalDescription(fn (Get $get) => new HtmlString(
                'Are you sure you want to convert this booking?<br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md'>This action will be logged and cannot be undone.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                '<p><strong>Fee:</strong> '.money($this->record->total_fee, $this->record->currency).'</p>'.
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->visible(fn () => $this->record->is_prime &&
                auth()->id() === 1 &&
                in_array($this->record->status, [
                    BookingStatus::CONFIRMED,
                    BookingStatus::VENUE_CONFIRMED,
                ]))
            ->action(function () {
                $result = ConvertToNonPrime::run($this->record);

                Notification::make()
                    ->success()
                    ->title('Booking Converted to Non Prime')
                    ->body($result['message'])
                    ->send();
            });
    }

    public function convertToPrimeBookingAction(): Action
    {
        return Action::make('convertToPrimeBooking')
            ->label('Convert to Prime')
            ->color('warning')
            ->icon('gmdi-price-check-o')
            ->requiresConfirmation()
            ->modalDescription(fn (Get $get) => new HtmlString(
                'Are you sure you want to convert this booking?<br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md'>This action will be logged and cannot be undone.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                '<p><strong>Fee:</strong> '.money($this->record->total_fee, $this->record->currency).'</p>'.
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->visible(fn () => ! $this->record->is_prime &&
                auth()->id() === 1 &&
                in_array($this->record->status, [
                    BookingStatus::CONFIRMED,
                    BookingStatus::VENUE_CONFIRMED,
                ]))
            ->action(function () {
                $result = ConvertToPrime::run($this->record);

                Notification::make()
                    ->success()
                    ->title('Booking Converted to Prime')
                    ->body($result['message'])
                    ->send();
            });
    }

    public function abandonBookingAction(): Action
    {
        return Action::make('abandonBooking')
            ->label('Mark as Abandoned')
            ->color('warning')
            ->icon('heroicon-o-x-mark')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading('Mark Booking as Abandoned')
            ->modalDescription(fn () => new HtmlString(
                'Are you sure you want to mark this booking as abandoned?<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md'>This action will be logged and cannot be undone.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->visible(fn () => in_array($this->record->status, [
                BookingStatus::PENDING,
                BookingStatus::GUEST_ON_PAGE,
            ]))
            ->action(function () {
                $this->record->update([
                    'status' => BookingStatus::ABANDONED,
                ]);

                // Delete any existing earnings
                $this->record->earnings()->delete();

                activity()
                    ->performedOn($this->record)
                    ->withProperties([
                        'guest_name' => $this->record->guest_name,
                        'venue_name' => $this->record->venue->name,
                        'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                        'guest_count' => $this->record->guest_count,
                        'previous_status' => $this->record->status,
                    ])
                    ->log('Booking marked as abandoned');

                Notification::make()
                    ->success()
                    ->title('Booking Abandoned')
                    ->body('The booking has been marked as abandoned.')
                    ->send();
            });
    }

    public function uncancelBookingAction(): Action
    {
        return Action::make('uncancelBooking')
            ->label('Uncancel Booking')
            ->color('warning')
            ->icon('heroicon-m-pencil-square')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading('Uncancel Booking')
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->modalDescription(fn () => new HtmlString(
                'Are you sure you want to modify this booking?<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold border rounded-md text-amber-600 bg-amber-100 border-amber-300'>This action will be logged.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->modalSubmitActionLabel('Uncancel')
            ->modalCancelActionLabel('Cancel')
            ->visible(fn () => auth()->id() === 1 && $this->record->status === BookingStatus::CANCELLED)
            ->action(fn () => $this->uncancelBooking());
    }

    private function uncancelBooking(): void
    {
        if (auth()->id() !== 1) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to uncancel bookings.')
                ->send();

            return;
        }

        // Calculate non-prime earnings and update status
        Booking::calculateNonPrimeEarnings($this->record, true);

        activity()
            ->performedOn($this->record)
            ->withProperties([
                'guest_name' => $this->record->guest_name,
                'venue_name' => $this->record->venue->name,
                'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                'guest_count' => $this->record->guest_count,
                'previous_status' => BookingStatus::CANCELLED->value,
                'new_status' => BookingStatus::CONFIRMED->value,
            ])
            ->log('Booking uncancelled');

        Notification::make()
            ->success()
            ->title('Booking Uncancelled')
            ->body('The booking has been successfully uncancelled and earnings restored.')
            ->send();
    }

    public function markAsNoShowAction(): Action
    {
        return Action::make('markAsNoShow')
            ->label('Mark as No-Show')
            ->color('danger')
            ->icon('mdi-ghost-outline')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalHeading('Mark as No-Show')
            ->modalDescription(fn () => new HtmlString(
                'Are you sure you want to mark this booking as a no-show?<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold text-red-600 bg-red-100 border border-red-300 rounded-md'>This action will reverse any concierge earnings and cannot be undone.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->visible(fn () => auth()->user()->hasAnyRole(['venue', 'venue_manager', 'super_admin']) &&
                ! $this->record->is_prime &&
                ! $this->record->no_show &&
                in_array($this->record->status, [
                    BookingStatus::CONFIRMED,
                    BookingStatus::VENUE_CONFIRMED,
                ]))
            ->action(function () {
                $this->markAsNoShow();
            });
    }

    private function markAsNoShow(): void
    {
        if (! auth()->user()->hasAnyRole(['venue', 'venue_manager', 'super_admin'])) {
            Notification::make()
                ->danger()
                ->title('Unauthorized')
                ->body('You do not have permission to mark bookings as no-show.')
                ->send();

            return;
        }

        // Dispatch the event to handle the no-show logic
        BookingMarkedAsNoShow::dispatch($this->record);

        activity()
            ->performedOn($this->record)
            ->withProperties([
                'guest_name' => $this->record->guest_name,
                'venue_name' => $this->record->venue->name,
                'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                'guest_count' => $this->record->guest_count,
                'marked_by' => auth()->user()->name,
                'marked_by_role' => auth()->user()->roles->pluck('name')->first(),
            ])
            ->log('Booking marked as no-show');

        Notification::make()
            ->success()
            ->title('Booking Marked as No-Show')
            ->body('The booking has been marked as no-show and concierge earnings have been reversed.')
            ->send();
    }

    public function transferBookingAction(): Action
    {
        $conciergeOptions = Concierge::query()
            ->where('id', '!=', $this->record->concierge_id)
            ->whereHas(
                'user',
                fn (Builder $query) => $query->whereHas('roles', fn (Builder $q) => $q->where('name', 'concierge'))
                    ->whereNull('suspended_at')
            )
            ->with('user')
            ->get()
            ->mapWithKeys(fn ($concierge) => [
                $concierge->id => $concierge->user->name.
                    ($concierge->hotel_name ? " ({$concierge->hotel_name})" : ''),
            ])
            ->toArray();

        return Action::make('transferBooking')
            ->label('Transfer Booking')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->hidden(fn () => ! auth()->user()->hasActiveRole('super_admin') ||
                ! in_array($this->record->status, [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]))
            ->form([
                Select::make('concierge_id')
                    ->label('New Concierge')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options($conciergeOptions),
            ])
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading('Transfer Booking')
            ->modalDescription(fn () => new HtmlString(
                'Are you sure you want to transfer this booking to another concierge?<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold border rounded-md text-amber-600 bg-amber-100 border-amber-300'>This action will transfer all earnings to the new concierge.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Current Concierge:</strong> {$this->record->concierge->user->name}</p>".
                "<p><strong>Venue:</strong> {$this->record->venue->name}</p>".
                "<p><strong>Guest Count:</strong> {$this->record->guest_count}</p>".
                "<p><strong>Booking Time:</strong> {$this->record->booking_at->format('M d, Y h:i A')}</p>".
                '</div>'
            ))
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->action(function (array $data): void {
                try {
                    /**
                     * @var Concierge $newConcierge
                     */
                    $newConcierge = Concierge::query()->findOrFail($data['concierge_id']);

                    $this->record->transferToConcierge($newConcierge);

                    Notification::make()
                        ->success()
                        ->title('Booking Transferred')
                        ->body("Booking has been transferred to {$newConcierge->user->name}")
                        ->send();
                } catch (InvalidArgumentException $e) {
                    Notification::make()
                        ->danger()
                        ->title('Transfer Failed')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function viewConciergeAction(): Action
    {
        return Action::make('viewConcierge')
            ->label(fn () => $this->record->concierge?->user->name ?? '')
            ->link()
            ->color('primary')
            ->modalHeading(fn () => $this->record->concierge?->user->name ?? '')
            ->modalContent(fn () => new HtmlString(
                "<div class='space-y-4'>".
                "<div class='grid grid-cols-2 gap-4 text-sm'>".
                '<div>'.
                "<span class='block font-medium text-gray-500'>Hotel/Company</span>".
                "<span class='block'>{$this->record->concierge?->hotel_name}</span>".
                '</div>'.
                '<div>'.
                "<span class='block font-medium text-gray-500'>Phone</span>".
                "<span class='block'>{$this->getFormattedPhoneNumber($this->record->concierge?->user->phone)}</span>".
                '</div>'.
                '<div>'.
                "<span class='block font-medium text-gray-500'>Email</span>".
                "<span class='block'>{$this->record->concierge?->user->email}</span>".
                '</div>'.
                '</div>'.
                '</div>'
            ))
            ->modalActions([
                Action::make('edit')
                    ->label('Edit')
                    ->url(fn () => route(
                        'filament.admin.resources.users.edit',
                        ['record' => $this->record->concierge?->user_id]
                    ))
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning'),
                Action::make('overview')
                    ->label('Overview')
                    ->url(fn () => route(
                        'filament.admin.resources.concierges.view',
                        ['record' => $this->record->concierge?->id]
                    ))
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-document-text')
                    ->color('info'),
                Action::make('bookings')
                    ->label('Bookings')
                    ->url(fn () => route(
                        'filament.admin.pages.booking-search',
                        ['filters' => ['concierge_search' => $this->record->concierge?->user->name]]
                    ))
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-calendar')
                    ->color('success'),
            ])
            ->modalWidth('md')
            ->visible(fn () => $this->record->concierge !== null);
    }

    public function modifyGuestInfoAction(): Action
    {
        return Action::make('modifyGuestInfo')
            ->label('Modify Guest Info')
            ->icon('heroicon-m-pencil-square')
            ->form([
                Grid::make(2)
                    ->schema([
                        TextInput::make('guest_first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => $this->record->guest_first_name),

                        TextInput::make('guest_last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => $this->record->guest_last_name),
                    ]),

                TextInput::make('guest_email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => $this->record->guest_email),

                TextInput::make('guest_phone')
                    ->label('Phone')
                    ->tel()
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => $this->record->guest_phone),

                Toggle::make('send_confirmation')
                    ->label('Send Updated Confirmation')
                    ->helperText('Send a new confirmation SMS to the guest with their updated information')
                    ->default(true),
            ])
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('indigo')
            ->modalHeading('Modify Guest Information')
            ->modalDescription("Are you sure you want to modify this customer's information?")
            ->modalWidth('md')
            ->action(function (array $data): void {
                $oldData = [
                    'guest_first_name' => $this->record->guest_first_name,
                    'guest_last_name' => $this->record->guest_last_name,
                    'guest_email' => $this->record->guest_email,
                    'guest_phone' => $this->record->guest_phone,
                ];

                // Format phone number
                $data['guest_phone'] = $this->getInternationalFormattedPhoneNumber($data['guest_phone']);

                $this->record->update([
                    'guest_first_name' => $data['guest_first_name'],
                    'guest_last_name' => $data['guest_last_name'],
                    'guest_email' => $data['guest_email'],
                    'guest_phone' => $data['guest_phone'],
                ]);

                // Log the activity
                activity()
                    ->performedOn($this->record)
                    ->withProperties([
                        'old_data' => $oldData,
                        'new_data' => $data,
                        'modified_by' => auth()->user()->name,
                        'modified_by_role' => auth()->user()->roles->pluck('name')->first(),
                        'confirmation_sent' => $data['send_confirmation'],
                    ])
                    ->log('Guest information modified');

                // Optionally resend confirmation to guest
                if ($data['send_confirmation']) {
                    $this->record->notify(new CustomerBookingConfirmed);
                    $notificationMessage = 'The guest information has been updated and a new confirmation has been sent.';
                } else {
                    $notificationMessage = 'The guest information has been updated.';
                }

                Notification::make()
                    ->success()
                    ->title('Guest Information Updated')
                    ->body($notificationMessage)
                    ->send();
            })
            ->visible(fn () => $this->canModifyBooking)
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full']);
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
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
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
                'booking' => $this->booking,
            ]))
            ->modalWidth('md')
            ->visible(! $this->download && auth()->check() && isset($this->canModifyBooking) && $this->canModifyBooking)
            ->disabled($this->booking->hasActiveModificationRequest())
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->extraAttributes(['class' => 'w-full']);
    }

    public function restoreConciergeEarningsAction(): Action
    {
        return Action::make('restoreConciergeEarnings')
            ->label('Restore Concierge Earnings')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading('Restore Concierge Earnings')
            ->modalDescription(fn () => new HtmlString(
                'Are you sure you want to restore the concierge earnings for this booking?<br><br>'.
                "<div class='text-sm'>".
                "<p class='p-1 mb-2 text-xs font-semibold border rounded-md text-amber-600 bg-amber-100 border-amber-300'>This will recreate the original earnings for the concierge.</p>".
                "<p class='text-lg font-semibold'>{$this->record->guest_name}</p>".
                "<p><strong>Concierge:</strong> {$this->record->concierge->user->name}</p>".
                '<p><strong>Original Amount:</strong> '.money(
                    $this->record->concierge_earnings,
                    $this->record->currency
                ).'</p>'.
                '</div>'
            ))
            ->visible(fn () => auth()->user()->hasActiveRole('super_admin') &&
                $this->record->is_refunded_or_partially_refunded)
            ->button()
            ->size('lg')
            ->extraAttributes(['class' => 'w-full'])
            ->action(function () {
                $this->restoreConciergeEarnings();
            });
    }

    private function restoreConciergeEarnings(): void
    {
        DB::transaction(function () {
            // Delete the refund earning
            Earning::query()
                ->where('booking_id', $this->record->id)
                ->where('user_id', $this->record->concierge->user_id)
                ->where('type', EarningType::CONCIERGE->value)
                ->where('percentage_of', 'refund')
                ->delete();

            // Check for existing concierge earning and update or create
            $existingEarning = Earning::query()
                ->where('booking_id', $this->record->id)
                ->where('user_id', $this->record->concierge->user_id)
                ->where('type', EarningType::CONCIERGE->value)
                ->where('percentage_of', 'total_fee')
                ->first();

            if ($existingEarning) {
                $existingEarning->update([
                    'amount' => $this->record->concierge_earnings,
                    'confirmed_at' => now(),
                ]);
            } else {
                Earning::query()->create([
                    'user_id' => $this->record->concierge->user_id,
                    'booking_id' => $this->record->id,
                    'type' => EarningType::CONCIERGE->value,
                    'amount' => $this->record->concierge_earnings,
                    'currency' => $this->record->currency,
                    'percentage' => $this->record->concierge->payout_percentage,
                    'percentage_of' => 'total_fee',
                    'confirmed_at' => now(),
                ]);
            }

            activity()
                ->performedOn($this->record)
                ->withProperties([
                    'guest_name' => $this->record->guest_name,
                    'venue_name' => $this->record->venue->name,
                    'booking_time' => $this->record->booking_at->format('M d, Y h:i A'),
                    'concierge_name' => $this->record->concierge->user->name,
                    'amount_restored' => $this->record->concierge_earnings,
                    'currency' => $this->record->currency,
                    'restored_by' => auth()->user()->name,
                ])
                ->log('Concierge earnings restored after refund');

            Notification::make()
                ->success()
                ->title('Concierge Earnings Restored')
                ->body('The concierge earnings have been successfully restored.')
                ->send();
        });
    }
}
