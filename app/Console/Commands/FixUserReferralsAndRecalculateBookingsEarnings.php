<?php

namespace App\Console\Commands;

use App\Actions\Booking\ConciergeReferralBookingsRecalculate;
use App\Actions\Booking\NonPrimeReferralBookingsRecalculate;
use App\Actions\Booking\PartnerReferralBookingsRecalculate;
use App\Actions\User\FixUserConciergeReferrals;
use App\Actions\User\FixUserPartnerReferrals;
use Illuminate\Console\Command;

class FixUserReferralsAndRecalculateBookingsEarnings extends Command
{
    protected $signature = 'prima:fix-user-referrals-recalculate-bookings';

    protected $description = 'Fix user referrals and recalculate bookings earnings';

    protected FixUserPartnerReferrals $fixUserPartnerReferrals;

    protected FixUserConciergeReferrals $fixUserConciergeReferrals;

    protected ConciergeReferralBookingsRecalculate $conciergeReferralBookingsRecalculate;

    protected PartnerReferralBookingsRecalculate $partnerReferralBookingsRecalculate;

    protected NonPrimeReferralBookingsRecalculate $nonPrimeReferralBookingsRecalculate;

    public function __construct(
        FixUserPartnerReferrals $fixUserPartnerReferrals,
        FixUserConciergeReferrals $fixUserConciergeReferrals,
        ConciergeReferralBookingsRecalculate $conciergeReferralBookingsRecalculate,
        PartnerReferralBookingsRecalculate $partnerReferralBookingsRecalculate,
        NonPrimeReferralBookingsRecalculate $nonPrimeReferralBookingsRecalculate
    )
    {
        parent::__construct();
        $this->fixUserPartnerReferrals = $fixUserPartnerReferrals;
        $this->fixUserConciergeReferrals = $fixUserConciergeReferrals;
        $this->conciergeReferralBookingsRecalculate = $conciergeReferralBookingsRecalculate;
        $this->partnerReferralBookingsRecalculate = $partnerReferralBookingsRecalculate;
        $this->nonPrimeReferralBookingsRecalculate = $nonPrimeReferralBookingsRecalculate;
    }

    public function handle(): void
    {
        $this->info('Running FixUserPartnerReferrals...');
        $partnerReferralsCount = $this->fixUserPartnerReferrals->handle();
        $this->info("FixUserPartnerReferrals affected {$partnerReferralsCount} records.");

        $this->info('Running FixUserConciergeReferrals...');
        $conciergeReferralsCount = $this->fixUserConciergeReferrals->handle();
        $this->info("FixUserConciergeReferrals affected {$conciergeReferralsCount} records.");

        $this->info('Running ConciergeReferralBookingsRecalculate...');
        $conciergeBookingsCount = $this->conciergeReferralBookingsRecalculate->handle();
        $this->info("ConciergeReferralBookingsRecalculate affected {$conciergeBookingsCount} records.");

        $this->info('Running PartnerReferralBookingsRecalculate...');
        $partnerBookingsCount = $this->partnerReferralBookingsRecalculate->handle();
        $this->info("PartnerReferralBookingsRecalculate affected {$partnerBookingsCount} records.");

        $this->info('Running NonPrimeReferralBookingsRecalculate...');
        $nonPrimeBookingsCount = $this->nonPrimeReferralBookingsRecalculate->handle();
        $this->info("NonPrimeReferralBookingsRecalculate affected {$nonPrimeBookingsCount} records.");
    }
}
