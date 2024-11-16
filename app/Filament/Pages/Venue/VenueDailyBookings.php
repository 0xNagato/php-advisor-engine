<?php

namespace App\Filament\Pages\Venue;

use App\Filament\Actions\Venue\MarkAsNoShowAction;
use App\Filament\Actions\Venue\ReverseMarkAsNoShowAction;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Venue;
use Filament\Pages\Page;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class VenueDailyBookings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.venue-daily-bookings';

    protected static ?string $slug = 'venue/bookings/{venue?}/{date?}';

    protected static bool $shouldRegisterNavigation = false;

    public Venue $venue;

    public string $date;

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('venue');
    }

    public function getHeading(): string|Htmlable
    {
        return date('D, M d, Y', strtotime($this->date));
    }

    public function mount(Venue $venue, string $date): void
    {
        $this->venue = $venue;
        $this->date = $date;
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmedOrNoShow()
            ->with('venue')
            ->select('bookings.*', 'earnings.amount as earnings')
            ->join('earnings', function ($join) {
                $join->on('earnings.booking_id', '=', 'bookings.id')
                    ->where('earnings.type', '=', 'venue')
                    ->where('earnings.user_id', '=', $this->venue->user_id);
            })
            ->whereDate('bookings.booking_at', $this->date)
            ->where('earnings.type', 'venue');

        return $table
            ->query($query)
            ->recordUrl(fn ($record) => ViewBooking::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(fn (Booking $record) => view('partials.booking-info-column', ['record' => $record])),
                TextColumn::make('earnings')
                    ->label('Earnings')
                    ->alignRight()
                    ->money(fn ($record) => $record->currency, divideBy: 100),
            ])
            ->actions([
                ActionGroup::make([
                    MarkAsNoShowAction::make('mark no show'),
                    ReverseMarkAsNoShowAction::make('reverse no show'),
                ])
                    ->tooltip('Actions')
                    ->hidden(fn () => ! auth()->user()?->hasActiveRole('venue')),
            ]);
    }
}
