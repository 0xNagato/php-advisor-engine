<?php

namespace App\Livewire\Concierge;

use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Concierge;
use App\Traits\ImpersonatesOther;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListConciergesTable extends BaseWidget
{
    use ImpersonatesOther;

    protected static ?string $heading = 'Concierges';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Concierge::query()
                    ->with(['user.referrer', 'user.authentications'])
                    ->withCount(['bookings', 'referrals'])
            )
            ->recordUrl(fn (Concierge $record) => ViewConcierge::getUrl(['record' => $record]))
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('user')
                    ->getStateUsing(fn (Concierge $record) => view('partials.concierge-user-info-column', [
                        'name' => $record->user->name,
                        'secured_at' => $record->user->secured_at,
                        'referrer_name' => $record->user->referrer?->name ?? '-',
                    ])),
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
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Concierge $record) => $this->impersonate($record->user)),
            ]);
    }
}
