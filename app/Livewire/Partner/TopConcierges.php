<?php

namespace App\Livewire\Partner;

use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Earning;
use App\Models\Partner;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class TopConcierges extends Widget
{
    protected static string $view = 'livewire.partner.top-concierges';

    protected static ?string $pollingInterval = null;

    #[Reactive]
    public ?Carbon $startDate = null;

    #[Reactive]
    public ?Carbon $endDate = null;

    public ?Partner $partner = null;

    public function getTopConcierges(): Collection
    {
        $currencyService = app(CurrencyConversionService::class);

        $earnings = Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->join('concierges', 'concierges.id', '=', 'bookings.concierge_id')
            ->join('users', 'users.id', '=', 'concierges.user_id')
            ->whereNotNull('bookings.confirmed_at')
            ->whereBetween('bookings.booking_at', [$this->startDate, $this->endDate])
            ->where('earnings.type', 'partner_concierge')
            ->where('earnings.user_id', $this->partner->user_id)
            ->groupBy('concierges.id', 'users.first_name', 'users.last_name', 'earnings.currency')
            ->select(
                'concierges.id as concierge_id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as concierge_name"),
                DB::raw('COUNT(DISTINCT bookings.id) as booking_count'),
                DB::raw('SUM(earnings.amount) as total_earned'),
                'earnings.currency'
            )
            ->get();

        return $earnings->groupBy('concierge_id')->map(function ($conciergeEarnings) use ($currencyService) {
            $totalUSD = $conciergeEarnings->sum(fn ($earning) => $currencyService->convertToUSD([$earning->currency => $earning->total_earned]));

            return [
                'concierge_id' => $conciergeEarnings->first()->concierge_id,
                'concierge_name' => $conciergeEarnings->first()->concierge_name,
                'booking_count' => $conciergeEarnings->sum('booking_count'),
                'total_usd' => $totalUSD,
            ];
        })->sortByDesc('total_usd')->values();
    }

    public function viewConcierge($conciergeId): void
    {
        $this->redirect(ViewConcierge::getUrl(['record' => $conciergeId]));
    }
}
