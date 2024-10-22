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
            ->where('type', 'concierge')
            ->whereNull('secured_at')
            ->whereNull('reminded_at')
            ->where('created_at', '<=', now()->subHours(48))
            ->where('created_at', '>', now()->subHours(72))
            ->get();

        foreach ($referrals as $referral) {
            $referral->notify(new InvitationReminder($referral));
            $referral->update(['reminded_at' => now()]);
        }

        $this->info("Sent {$referrals->count()} concierge reminders.");
        logger()->info("Sent {$referrals->count()} concierge reminders.");
    }
}
