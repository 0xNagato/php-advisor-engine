<?php

namespace App\Livewire\Partner;

use App\Models\Partner;
use App\Models\Referral;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ReferralTable extends BaseWidget
{
    public ?Partner $partner = null;

    protected static ?string $heading = 'Referrals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Referral::query()
                    ->with(['user.concierge', 'user.venue'])
                    ->orderByDesc('secured_at')
                    ->where('referrer_id', $this->partner->user->id)
            )
            ->recordUrl(fn (Referral $referral): string => $referral->view_route)
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->formatStateUsing(fn (Referral $record): string => view('components.two-line-cell', [
                        'primary' => $record->user->name,
                        'secondary' => ucfirst($record->type),
                    ])->render())
                    ->html()
                    ->size('sm'),
                TextColumn::make('secured_at')
                    ->label('Date')
                    ->size('xs')
                    ->date()
                    ->timezone(auth()->user()->timezone)
                    ->alignment(Alignment::End),
            ]);
    }
}
