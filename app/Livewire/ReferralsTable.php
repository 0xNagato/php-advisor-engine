<?php

namespace App\Livewire;

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
                Auth::user()?->concierge->referrals()->getQuery()
            )
            ->columns([
                TextColumn::make('label')
                    ->label('Referral'),
                IconColumn::make('has_secured')
                    ->label('Secured')
                    ->alignCenter()
                    ->icon(fn(string $state): string => empty($state) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(string $state): string => empty($state) ? 'danger' : 'success'),
            ]);
    }
}
