<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use Illuminate\View\View;
use Livewire\Component;

class EarningsBreakdown extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load(
            'earnings.user.venue',
            'earnings.user.concierge',
            'earnings.user.partner'
        );
    }

    private function calculatePlatformEarnings(): int
    {
        return $this->booking->is_refunded_or_partially_refunded
            ? $this->booking->final_platform_earnings_total
            : $this->booking->platform_earnings;
    }

    private function calculateTotalWithTax(): int
    {
        return $this->booking->is_refunded_or_partially_refunded
            ? $this->booking->final_total
            : $this->booking->total_with_tax_in_cents;
    }

    private function calculateGrossRevenue(): int
    {
        if ($this->booking->is_prime) {
            return $this->booking->total_fee;
        }

        return abs($this->booking->venue_earnings);
    }

    private function calculatePrimaShare(): int
    {
        return $this->calculatePlatformEarnings();
    }

    public function render(): View
    {
        $viewName = auth()->id() === 1
            ? 'livewire.booking.earnings-breakdown-advanced'
            : 'livewire.booking.earnings-breakdown';

        $viewName = 'livewire.booking.earnings-breakdown';

        return view($viewName, [
            'groupedEarnings' => $this->booking->earnings->sumByUserAndType(),
            'platformEarnings' => $this->calculatePlatformEarnings(),
            'currency' => $this->booking->currency,
            'totalWithTax' => $this->calculateTotalWithTax(),
            'grossRevenue' => $this->calculateGrossRevenue(),
            'primaShare' => $this->calculatePrimaShare(),
        ]);
    }
}
