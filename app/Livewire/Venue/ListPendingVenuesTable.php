<?php

namespace App\Livewire\Venue;

use App\Models\Referral;
use App\Notifications\Concierge\NotifyConciergeReferral;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListPendingVenuesTable extends BaseWidget
{
    protected static ?string $heading = 'Pending Venues';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Referral::query()->where(['type' => 'venue', 'secured_at' => null])
            )
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('id')
                    ->label('User')
                    ->formatStateUsing(fn (Referral $record) => view('partials.pending-concierge-info-column', ['record' => $record])),
                TextColumn::make('created_at')
                    ->label('Invited')
                    ->dateTime('D, M j, Y g:ia', auth()->user()->timezone),
            ])->actions([
                Action::make('resendInvitation')
                    ->icon('ri-refresh-line')
                    ->iconButton()
                    ->color('indigo')
                    ->requiresConfirmation()
                    ->action(function (Referral $record) {
                        if (! blank($record->phone)) {
                            $record->notify(new NotifyConciergeReferral(referral: $record, channel: 'sms'));
                        } elseif (! blank($record->email)) {
                            $record->notify(new NotifyConciergeReferral(referral: $record, channel: 'mail'));
                        }

                        $record->update(['notified_at' => Carbon::now()]);

                        Notification::make()
                            ->title('Invite sent successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
