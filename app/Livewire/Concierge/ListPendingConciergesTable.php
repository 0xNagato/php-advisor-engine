<?php

namespace App\Livewire\Concierge;

use App\Models\Referral;
use App\Notifications\Concierge\NotifyConciergeReferral;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ListPendingConciergesTable extends BaseWidget
{
    protected static ?string $heading = 'Pending Concierges';

    public string $search = '';
    public string $dateFilter = 'all_time';
    public string $startDate = '';
    public string $endDate = '';

        protected function getTableQuery(): Builder
    {
        $query = Referral::query()
            ->where(['type' => 'concierge', 'secured_at' => null])
            ->orderBy('created_at', 'desc'); // Latest first

        // Apply search filter from properties
        if (filled($this->search)) {
            $search = strtolower($this->search);
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw('LOWER(first_name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(last_name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(phone) like ?', ["%{$search}%"]);
            });
        }

        // Apply date filter for invitation date from properties
        if ($this->dateFilter === 'date_range' && filled($this->startDate) && filled($this->endDate)) {
            $startDate = Carbon::parse($this->startDate)->startOfDay();
            $endDate = Carbon::parse($this->endDate)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('User')
                    ->size('xs')
                    ->formatStateUsing(fn (Referral $record) => view('partials.pending-concierge-info-column', ['record' => $record])),
                TextColumn::make('created_at')
                    ->label('Invited')
                    ->size('xs')
                    ->formatStateUsing(function ($state) {
                        $date = Carbon::parse($state);

                        return $date->isCurrentYear() ? $date->format('M j, g:ia') : $date->format('M j, Y g:ia');
                    }),
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
