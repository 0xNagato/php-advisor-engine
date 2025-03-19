<?php

namespace App\Livewire\Admin;

use App\Actions\Booking\SendModificationRequestToVenueContacts;
use App\Models\BookingModificationRequest;
use App\Notifications\Booking\ConciergeModificationApproved;
use App\Notifications\Booking\ConciergeModificationRejected;
use App\Notifications\Booking\CustomerModificationApproved;
use App\Notifications\Booking\CustomerModificationRejected;
use App\Services\Booking\BookingCalculationService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConfirmationManagerModifications extends BaseWidget
{
    protected static ?string $heading = '';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BookingModificationRequest::query()
                    ->orderByRaw("status = '".BookingModificationRequest::STATUS_PENDING."' DESC")
                    ->orderBy('created_at', 'desc')
                    ->with('booking.venue')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('Venue')
                    ->formatStateUsing(fn (BookingModificationRequest $record
                    ) => view(
                        'partials.booking-modification-request-column',
                        ['record' => $record]
                    ))
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('resendConfirmation')
                        ->hidden(fn (BookingModificationRequest $record) => ! auth()->user()->hasActiveRole('super_admin') ||
                            $record->status !== BookingModificationRequest::STATUS_PENDING)
                        ->label('Resend Confirmation')
                        ->requiresConfirmation()
                        ->icon(fn (BookingModificationRequest $record
                        ) => is_null($record->responded_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                        ->color(fn (BookingModificationRequest $record
                        ) => is_null($record->responded_at) ? 'indigo' : 'success')
                        ->requiresConfirmation()
                        ->action(function (BookingModificationRequest $record) {
                            SendModificationRequestToVenueContacts::run($record);
                        }),
                    Action::make('markAsApproved')
                        ->hidden(fn (BookingModificationRequest $record) => ! auth()->user()->hasActiveRole('super_admin') ||
                            $record->status !== BookingModificationRequest::STATUS_PENDING)
                        ->label('Mark as Approved')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn (BookingModificationRequest $record) => $this->approveModification($record)),
                    Action::make('markAsRejected')
                        ->hidden(fn (BookingModificationRequest $record) => ! auth()->user()->hasActiveRole('super_admin') ||
                            $record->status !== BookingModificationRequest::STATUS_PENDING)
                        ->label('Mark as Rejected')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->form([
                            Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->required(),
                        ])
                        ->action(fn (
                            BookingModificationRequest $record,
                            array $data
                        ) => $this->rejectModification($record, $data['reason'])),
                ]),
            ]);
    }

    /**
     * @throws Throwable
     */
    public function approveModification(BookingModificationRequest $modificationRequest): void
    {
        DB::transaction(function () use ($modificationRequest) {
            $modificationRequest->markAsApproved();

            // Delete existing earnings before updating booking
            $modificationRequest->booking->earnings()->delete();

            // Update the booking with new details using DB facade
            DB::table('bookings')
                ->where('id', $modificationRequest->booking->id)
                ->update([
                    'guest_count' => $modificationRequest->requested_guest_count,
                    'schedule_template_id' => $modificationRequest->requested_schedule_template_id,
                    'booking_at' => $modificationRequest->booking->booking_at->format('Y-m-d').' '.
                        $modificationRequest->requested_time,
                ]);

            // Recalculate earnings with the new booking details
            app(BookingCalculationService::class)->calculateEarnings($modificationRequest->booking->refresh());

            // Send notifications
            $modificationRequest->notify(new CustomerModificationApproved);
            $modificationRequest->notify(new ConciergeModificationApproved);

            activity()
                ->performedOn($modificationRequest->booking)
                ->withProperties([
                    'modification_request_id' => $modificationRequest->id,
                    'status' => 'approved',
                ])
                ->log('Venue approved booking modification request');
        });

        Notification::make()
            ->success()
            ->title('Modification Request Approved')
            ->body('The booking has been updated and all parties will be notified.')
            ->send();
    }

    public function rejectModification(BookingModificationRequest $modificationRequest, ?string $reason = null): void
    {
        $modificationRequest->markAsRejected($reason);

        // Send notifications
        $modificationRequest->notify(new CustomerModificationRejected);
        $modificationRequest->notify(new ConciergeModificationRejected);

        activity()
            ->performedOn($modificationRequest->booking)
            ->withProperties([
                'modification_request_id' => $modificationRequest->id,
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
}
