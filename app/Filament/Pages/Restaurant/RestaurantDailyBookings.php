<?php

namespace App\Filament\Pages\Restaurant;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Restaurant;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class RestaurantDailyBookings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.restaurant-daily-bookings';

    protected static ?string $slug = 'restaurant/bookings/{restaurant?}/{date?}';
    protected static bool $shouldRegisterNavigation = false;

    public Restaurant $restaurant;
    public string $date;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return date('D, M d, Y', strtotime($this->date));
    }
    

    public function mount(Restaurant $restaurant, string $date): void
    {
        $this->restaurant = $restaurant;
        $this->date = $date;
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmed()
            ->select('bookings.*', 'earnings.amount as earnings')
            ->join('schedules', 'bookings.schedule_id', '=', 'schedules.id')
            ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
            ->where('schedules.restaurant_id', $this->restaurant->id)
            ->whereDate('bookings.booking_at', $this->date)
            ->where('earnings.type', 'restaurant');

        return $table
            ->query($query)
            ->recordUrl(fn($record) => ViewBooking::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(function (Booking $record) {
                        return view('partials.booking-info-column', ['record' => $record]);
                    }),
                TextColumn::make('earnings')
                    ->label('Earnings')
                    ->alignRight()
                    ->money('USD', divideBy: 100)
            ]);
    }
}
