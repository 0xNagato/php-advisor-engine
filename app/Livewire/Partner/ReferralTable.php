<?php

namespace App\Livewire\Partner;

use App\Models\Partner;
use App\Models\Referral;
use Exception;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class ReferralTable extends BaseWidget
{
    use InteractsWithPageFilters;

    public ?Partner $partner = null;

    protected static ?string $heading = 'Referrals';

    public int|string|array $columnSpan;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Referral::query()
                    ->with(['user.concierge', 'user.venue'])
                    ->orderByDesc('secured_at')
                    ->where('referrer_id', $this->partner->user->id)
            )
            ->recordUrl(fn (Referral $referral): ?string => $referral->view_route ?? '#')
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn (Referral $record): string => view('components.two-line-cell', [
                        'primary' => $record->user->name,
                        'secondary' => ucfirst($record->type),
                    ])->render())
                    ->html()
                    ->size('sm'),
                TextColumn::make('secured_at')
                    ->label('Date Joined')
                    ->size('xs')
                    ->date()
                    ->timezone(auth()->user()->timezone)
                    ->alignment(Alignment::End),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'concierge' => 'Concierge',
                        'venue' => 'Venue',
                    ])
                    ->label('Referral Type'),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
