<?php

namespace App\Livewire\Partner;

use App\Events\ConciergeReferredViaText;
use App\Filament\Pages\Partner\ConciergeEarnings;
use App\Models\Referral;
use App\Notifications\ConciergeReferredEmail;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ConciergeReferralsTable extends BaseWidget
{
    public static ?string $heading = 'Concierges';

    protected static bool $isLazy = true;

    public int|string|array $columnSpan;

    protected $listeners = ['concierge-referred' => '$refresh'];

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                auth()->user()->referrals()
                    ->with('user.concierge')
                    ->orderBy('created_at', 'desc')
                    ->where('type', 'concierge')
                    ->getQuery()
            )
            ->recordUrl(function (Referral $record) {
                if ($record->has_secured) {
                    return ConciergeEarnings::getUrl([$record->user->concierge->id]);
                }

                return null;
            })
            ->emptyStateHeading('No concierges found.')
            ->columns([
                TextColumn::make('label')
                    ->label('Referral')
                    ->formatStateUsing(fn(Referral $record) => view('partials.concierge-referral-info-column', ['record' => $record])),
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
                    ->hidden(fn (Referral $record) => $record->has_secured)
                    ->action(function (Referral $record) {
                        if (! blank($record->phone)) {
                            ConciergeReferredViaText::dispatch($record);
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
