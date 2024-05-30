<?php

namespace App\Livewire\Concierge;

use App\Events\ConciergeReferredViaText;
use App\Models\Referral;
use App\Notifications\ConciergeReferredEmail;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListPendingConciergesTable extends BaseWidget
{
    protected static ?string $heading = 'Pending Concierges';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Referral::query()->where(['type' => 'concierge', 'secured_at' => null])
            )
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
                            ConciergeReferredViaText::dispatch($record);
                        } elseif (! blank($record->email)) {
                            $record->notify(new ConciergeReferredEmail($record));
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
