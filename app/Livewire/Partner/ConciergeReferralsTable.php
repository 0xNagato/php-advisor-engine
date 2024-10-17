<?php

namespace App\Livewire\Partner;

use App\Filament\Pages\Partner\ConciergeEarnings;
use App\Models\Referral;
use App\Notifications\Concierge\NotifyConciergeReferral;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

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
                    ->formatStateUsing(fn (Referral $record) => view('partials.concierge-referral-info-column', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->actions([
                Action::make('activeStatus')
                    ->icon(fn (Referral $record): string => $record->has_secured ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (Referral $record): string => $record->has_secured ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Referral')
                    ->modalContent(fn (Referral $record): HtmlString => new HtmlString(
                        "<div class='text-center'>
                            <p class='mb-2 text-xl font-bold'>{$record->name}</p>
                            <p class='text-sm text-gray-600'>{$record->phone}</p>
                            <p class='text-sm text-gray-600'>{$record->email}</p>
                        </div>"
                    ))
                    ->modalIcon('heroicon-o-trash')
                    ->action(function (Referral $record) {
                        if (!$record->has_secured) {
                            $this->deleteReferral($record);
                        }
                    })
                    ->disabled(fn (Referral $record) => $record->has_secured)
                    ->hiddenLabel()
                    ->tooltip(fn (Referral $record): string => $record->has_secured ? 'Active' : 'Delete')
                    ->size('lg'),
                Action::make('resendInvitation')
                    ->icon('ri-refresh-line')
                    ->iconButton()
                    ->color('indigo')
                    ->requiresConfirmation()
                    ->hidden(fn (Referral $record) => $record->has_secured)
                    ->action(function (Referral $record) {
                        if (! blank($record->phone)) {
                            $record->notify(new NotifyConciergeReferral(referral: $record, channel: 'sms'));
                        } elseif (! blank($record->email)) {
                            $record->notify(new NotifyConciergeReferral(referral: $record, channel: 'mail'));
                        }

                        Notification::make()
                            ->title('Invite sent successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
