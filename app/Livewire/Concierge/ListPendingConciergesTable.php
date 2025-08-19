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
use Filament\Widgets\Concerns\CanPoll;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListPendingConciergesTable extends BaseWidget
{
    use CanPoll;

    protected static ?string $heading = 'Pending Concierges';

    public ?array $filters = [];

    public function mount(): void
    {
        // Listen for filter updates from the parent page
        $this->filters = [];
    }

    #[On('updatePendingFilters')]
    public function updateFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        $query = Referral::query()
            ->where(['type' => 'concierge', 'secured_at' => null])
            ->orderBy('created_at', 'desc'); // Latest first

        // Apply search filter
        if (filled($this->filters['search'] ?? '')) {
            $search = strtolower($this->filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw('LOWER(first_name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(last_name) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(phone) like ?', ["%{$search}%"]);
            });
        }

        // Apply date filter for invitation date
        if (($this->filters['date_filter'] ?? 'all_time') === 'date_range' &&
            filled($this->filters['start_date'] ?? '') && filled($this->filters['end_date'] ?? '')) {
            $startDate = Carbon::parse($this->filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($this->filters['end_date'])->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
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
