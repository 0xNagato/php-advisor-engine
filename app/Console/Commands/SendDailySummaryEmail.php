<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Mail\DailySummaryEmail;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Referral;
use App\Models\User;
use App\Models\Venue;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendDailySummaryEmail extends Command
{
    protected $signature = 'app:send-daily-summary-email';

    protected $description = 'Send a daily summary email to super admins';

    public function handle()
    {
        $yesterday = Carbon::yesterday();
        $startDate = $yesterday->startOfDay();
        $endDate = $yesterday->endOfDay();

        $bookings = $this->getBookingsData($startDate, $endDate);
        $totalBookings = $bookings->sum('count');

        $currencyService = app(CurrencyConversionService::class);
        $totalAmountUSD = $currencyService->convertToUSD($bookings->pluck('total_amount', 'currency')->toArray());
        $platformEarningsUSD = $currencyService->convertToUSD($bookings->pluck('platform_earnings', 'currency')->toArray());

        $newConciergesInvited = Referral::where('type', 'concierge')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $newConciergesSecured = User::role('concierge')
            ->whereBetween('secured_at', [$startDate, $endDate])
            ->count();

        $newConcierges = Concierge::with('user')
            ->whereHas('user', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get()
            ->map(function ($concierge) {
                return [
                    'name' => $concierge->user->name,
                    'email' => $concierge->user->email,
                    'profile_url' => route('filament.resources.concierges.view', $concierge->id),
                ];
            });

        $summary = [
            'date' => $yesterday->toDateString(),
            'new_bookings' => $totalBookings,
            'new_venues' => Venue::whereDate('created_at', $yesterday)->count(),
            'new_concierges_invited' => $newConciergesInvited,
            'new_concierges_secured' => $newConciergesSecured,
            'new_concierges_list' => $newConcierges,
            'total_amount' => $totalAmountUSD,
            'platform_earnings' => $platformEarningsUSD,
            'currency_breakdown' => $bookings->pluck('total_amount', 'currency')->toArray(),
        ];

        $superAdmins = User::role('super_admin')->get();

        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->send(new DailySummaryEmail($summary));
        }

        $this->info('Daily summary email sent to super admins.');
        logger()->info('Daily Summary:', $summary);
    }

    protected function getBookingsData($startDate, $endDate)
    {
        return Booking::query()
            ->whereBetween('confirmed_at', [$startDate, $endDate])
            ->where('status', BookingStatus::CONFIRMED)
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_fee) as total_amount'),
                DB::raw('SUM(platform_earnings) as platform_earnings'),
                'currency'
            )
            ->groupBy('currency')
            ->get();
    }
}
