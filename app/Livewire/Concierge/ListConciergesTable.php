<?php

namespace App\Livewire\Concierge;

use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Concierge;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListConciergesTable extends BaseWidget
{
    protected static ?string $heading = 'Concierges';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Concierge::query()
                    ->with(['user.referrer'])
                    ->withCount(['bookings', 'referrals'])
            )
            ->recordUrl(fn (Concierge $record) => ViewConcierge::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('user')
                    ->formatStateUsing(fn (Concierge $record) => view('partials.concierge-user-info-column', ['record' => $record->user])),
                TextColumn::make('hotel_name')
                    ->label('Co Name'),
                TextColumn::make('id')
                    ->label('Bookings')
                    ->formatStateUsing(function (Concierge $record) {
                        $bookingsCount = $record->bookings_count;
                        $earnings = $record->user->earnings()->confirmed()->sum('amount');

                        return view('partials.concierge-earnings-info-column', ['bookingsCount' => $bookingsCount, 'earnings' => $earnings]);
                    }),
                TextColumn::make('referrals_count')
                    ->label('Referrals')
                    ->alignCenter()
                    ->numeric(),
                TextColumn::make('user.authentications.login_at')
                    ->formatStateUsing(fn (Concierge $record) => Carbon::parse($record->user->authentications()->orderByDesc('login_at')->first()->login_at, auth()->user()->timezone)
                        ->diffForHumans())
                    ->label('Last Login'),
            ]);
    }
}
