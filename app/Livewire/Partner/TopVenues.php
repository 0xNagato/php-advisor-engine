<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\VenueResource\Pages\ViewVenue;
use App\Models\Earning;
use App\Models\Partner;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class TopVenues extends Widget
{
    protected static string $view = 'livewire.partner.top-venues';

    protected static ?string $pollingInterval = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public ?Partner $partner = null;

    public function getTopVenues(): Collection
    {
        $currencyService = app(CurrencyConversionService::class);

        $earnings = Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
            ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereBetween('bookings.booking_at', [$this->startDate, $this->endDate])
            ->where('earnings.type', 'partner_venue')
            ->where('earnings.user_id', $this->partner->user_id)
            ->groupBy('venues.id', 'venues.name', 'earnings.currency')
            ->limit(10)
            ->select(
                'venues.id as venue_id',
                'venues.name as venue_name',
                DB::raw('COUNT(DISTINCT bookings.id) as booking_count'),
                DB::raw('SUM(earnings.amount) as total_earned'),
                'earnings.currency'
            )
            ->get();

        $venueTotals = $earnings->groupBy('venue_id')->map(function ($venueEarnings) use ($currencyService) {
            $totalUSD = $venueEarnings->sum(fn ($earning) => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]));

            return [
                'venue_id' => $venueEarnings->first()->venue_id,
                'venue_name' => $venueEarnings->first()->venue_name,
                'booking_count' => $venueEarnings->sum('booking_count'),
                'total_usd' => $totalUSD,
            ];
        })->sortByDesc('total_usd')->values();

        return $venueTotals;
    }

    public function viewVenue($venueId): void
    {
        $this->redirect(ViewVenue::getUrl(['record' => $venueId]));
    }
}
