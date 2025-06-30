<?php

namespace App\Filament\Pages\Venue;

use App\Models\Booking;
use App\Models\Venue;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VenueBookings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'gmdi-restaurant-menu-o';

    protected static string $view = 'filament.pages.venue-bookings';

    protected static ?string $slug = 'venue/bookings';

    protected static ?string $title = 'Daily Bookings';

    /** @var Collection<int, Venue> */
    protected Collection $venues;

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole(['venue', 'venue_manager']);
    }

    public function mount(): void
    {
        if (auth()->user()->hasActiveRole('venue')) {
            $this->venues = Venue::query()
                ->where('id', auth()->user()->venue->id)
                ->get();
        } elseif (auth()->user()->hasActiveRole('venue_manager')) {
            $venueGroup = auth()->user()->currentVenueGroup();
            $allowedVenueIds = $venueGroup?->getAllowedVenueIds(auth()->user()) ?? [];

            $this->venues = $venueGroup?->venues()
                ->when(filled($allowedVenueIds),
                    fn ($query) => $query->whereIn('id', $allowedVenueIds),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->get() ?? Venue::query()->whereRaw('1 = 0')->get();
        } else {
            abort(403, 'You are not authorized to access this page');
        }
    }

    public function table(Table $table): Table
    {
        /** @var Builder $query */
        $query = Booking::confirmedOrNoShow()
            ->selectRaw('MIN(bookings.id) as id, DATE(bookings.booking_at) as booking_date, COUNT(*) as number_of_bookings')
            ->addSelect(DB::raw('SUM(earnings.amount) as earnings'))
            ->join('earnings', 'bookings.id', '=', 'earnings.booking_id')
            ->join('schedule_with_bookings', function ($join) {
                $join->on('bookings.schedule_template_id', '=', 'schedule_with_bookings.schedule_template_id')
                    ->whereColumn('bookings.booking_at', '=', 'schedule_with_bookings.booking_at');
            })
            ->whereIn('earnings.type', ['venue', 'venue_paid'])
            ->whereIn('schedule_with_bookings.venue_id', $this->venues->pluck('id'))
            ->groupBy(DB::raw('DATE(bookings.booking_at)'))
            ->orderByRaw('DATE(bookings.booking_at) DESC');

        return $table
            ->query($query)
            ->recordUrl(fn ($record) => VenueDailyBookings::getUrl([
                'venue' => $this->venues->first(), 'date' => $record->booking_date,
            ]))
            ->columns([
                TextColumn::make('booking_date')
                    ->date('D, M d, Y')
                    ->label('Booking Date'),
                TextColumn::make('number_of_bookings')
                    ->numeric()
                    ->alignRight()
                    ->label('Bookings'),
                TextColumn::make('earnings')
                    ->label('Earnings')
                    ->alignRight()
                    ->money(fn ($record) => $record->currency, divideBy: 100),
            ]);
    }
}
