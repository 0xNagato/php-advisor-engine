<?php

namespace App\Console\Commands;

use App\Models\Referral;
use App\Notifications\Concierge\InvitationReminder;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Console\Command;

class SendConciergeInvitationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-concierge-invitation-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to concierges who haven\'t secured their account after 48 hours';

    /**
     * Execute the console command.
     *
     * @throws ShortURLException
     */
    public function handle(): void
    {
        $referrals = Referral::query()
            ->with('referrer')
            ->where('type', 'concierge')
            ->whereNull('secured_at')
            ->whereNull('reminded_at')
            ->where('created_at', '<=', now()->subHours(48))
            ->get();

        $smsCount = 0;
        $emailCount = 0;

        foreach ($referrals as $referral) {
            $referral->notify(new InvitationReminder($referral));

            if ($referral->phone) {
                $smsCount++;
            }
            if ($referral->email) {
                $emailCount++;
            }

            $referral->update(['reminded_at' => now()]);
        }

        $totalCount = $smsCount + $emailCount;
        $this->info("Sent {$totalCount} concierge reminders ({$smsCount} SMS, {$emailCount} emails).");
        logger()->info("Sent {$totalCount} concierge reminders ({$smsCount} SMS, {$emailCount} emails).");
    }
}
