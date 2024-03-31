<?php

namespace App\Livewire;

use App\Filament\Pages\Concierge\ConciergeReferralEarnings;
use App\Models\ConciergeReferral;
use App\Notifications\ConciergeReferredEmail;
use App\Notifications\ConciergeReferredText;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ReferralsTable extends BaseWidget
{
    public static ?string $heading = 'Referrals';

    protected $listeners = ['concierge-referred' => '$refresh'];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Auth::user()?->concierge->referrals()->orderBy('created_at', 'desc')->getQuery()
            )
            ->recordUrl(function (ConciergeReferral $record) {
                if ($record->has_secured) {
                    return ConciergeReferralEarnings::getUrl([$record->user->concierge->id]);
                }

                return null;
            })
            ->columns([
                TextColumn::make('label')
                    ->label('Referral'),
                IconColumn::make('has_secured')
                    ->label('Active')
                    ->alignCenter()
                    ->icon(fn (string $state): string => empty($state) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (string $state): string => empty($state) ? 'danger' : 'success'),
            ])
            ->actions([
                Action::make('resendInvitation')
                    ->icon('ri-refresh-line')
                    ->iconButton()
                    ->color('indigo')
                    ->requiresConfirmation()
                    ->hidden(fn (ConciergeReferral $record) => $record->has_secured)
                    ->action(function (ConciergeReferral $record) {
                        if (! blank($record->phone)) {
                            $record->notify(new ConciergeReferredText($record));
                        } elseif (! blank($record->email)) {
                            $record->notify(new ConciergeReferredEmail($record));
                        }

                        Notification::make()
                            ->title('Invite sent successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
