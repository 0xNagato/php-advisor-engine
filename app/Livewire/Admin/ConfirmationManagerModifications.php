<?php

namespace App\Livewire\Admin;

use App\Actions\Booking\SendModificationRequestToVenueContacts;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\BookingModificationRequest;
use App\Services\BookingModificationService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
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
            ->recordUrl(fn (BookingModificationRequest $record) => ViewBooking::getUrl(['record' => $record->booking]))
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
                        ->hidden(fn (BookingModificationRequest $record
                        ) => ! auth()->user()->hasActiveRole('super_admin') ||
                            $record->status !== BookingModificationRequest::STATUS_PENDING)
                        ->label('Resend Confirmation')
                        ->requiresConfirmation()
                        ->icon(fn (BookingModificationRequest $record
                        ) => is_null($record->responded_at) ? 'ri-refresh-line' : 'heroicon-o-check-circle')
                        ->color(fn (BookingModificationRequest $record
                        ) => is_null($record->responded_at) ? 'indigo' : 'success')
                        ->requiresConfirmation()
                        ->visible(fn (BookingModificationRequest $record) => $record->booking->booking_at_utc < now())
                        ->action(function (BookingModificationRequest $record) {
                            SendModificationRequestToVenueContacts::run($record);
                        }),
                    Action::make('markAsApproved')
                        ->hidden(fn (BookingModificationRequest $record
                        ) => ! auth()->user()->hasActiveRole('super_admin') ||
                            $record->status !== BookingModificationRequest::STATUS_PENDING)
                        ->label('Mark as Approved')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn (BookingModificationRequest $record) => $this->approveModification($record)),
                    Action::make('markAsRejected')
                        ->hidden(fn (BookingModificationRequest $record
                        ) => ! auth()->user()->hasActiveRole('super_admin') ||
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
        app(BookingModificationService::class)
            ->approve($modificationRequest, 'Super Admin');

        Notification::make()
            ->success()
            ->title('Modification Request Approved')
            ->body('The booking has been updated and all parties will be notified.')
            ->send();
    }

    public function rejectModification(BookingModificationRequest $modificationRequest, ?string $reason = null): void
    {
        app(BookingModificationService::class)
            ->reject($modificationRequest, $reason, 'Super Admin');

        Notification::make()
            ->success()
            ->title('Modification Request Rejected')
            ->body('All parties will be notified of the rejection.')
            ->send();
    }
}
