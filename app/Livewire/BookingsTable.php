<?php

namespace App\Livewire;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class BookingsTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    public User $user;

    public function table(Table $table): Table
    {
        $query = Booking::query()
            ->with([
                'venue',
                'concierge.user',
                'partnerConcierge.user',
                'partnerVenue.user',
                'schedule',
            ])
            ->whereNotNull('confirmed_at')
            ->where(function (EloquentBuilder $query) {
                // Venue bookings
                $query->when($this->user->venue, function (Builder $query) {
                    $query->orWhereHas('schedule', function (Builder $query) {
                        $query->where('venue_id', $this->user->venue->id);
                    });
                })
                // Concierge bookings
                    ->when($this->user->concierge, function (Builder $query) {
                        $query->orWhere('concierge_id', $this->user->concierge->id);
                    })
                // Partner bookings (both venue and concierge referrals)
                    ->when($this->user->partner, function (Builder $query) {
                        $query->orWhere('partner_venue_id', $this->user->partner->id)
                            ->orWhere('partner_concierge_id', $this->user->partner->id);
                    });
            })
            ->orderByDesc('confirmed_at');

        return $table
            ->query($query)
            ->recordUrl(fn (Booking $record) => ViewBooking::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable(),

                TextColumn::make('booking_at')
                    ->label('Date')
                    ->dateTime('D, M j, Y g:ia'),

                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->formatStateUsing(fn (Booking $record) => "{$record->guest_name} ({$record->guest_count})"),

                TextColumn::make('role')
                    ->label('Your Role')
                    ->getStateUsing(function (Booking $record) {
                        $roles = [];

                        if ($this->user->venue?->id === $record->schedule?->venue_id) {
                            $roles[] = 'Venue';
                        }
                        if ($this->user->concierge?->id === $record->concierge_id) {
                            $roles[] = 'Concierge';
                        }
                        if ($this->user->partner) {
                            if ($record->partner_venue_id === $this->user->partner->id) {
                                $roles[] = 'Partner (Venue)';
                            }
                            if ($record->partner_concierge_id === $this->user->partner->id) {
                                $roles[] = 'Partner (Concierge)';
                            }
                        }

                        return empty($roles) ? '-' : implode(' & ', $roles);
                    }),

                TextColumn::make('total_fee')
                    ->label('Total Fee')
                    ->money(fn (Booking $record) => $record->currency)
                    ->state(fn (Booking $record) => $record->total_fee / 100)
                    ->alignRight(),
            ])
            ->defaultSort('confirmed_at', 'desc')
            ->paginated([10, 25, 50])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ]);
    }
}
