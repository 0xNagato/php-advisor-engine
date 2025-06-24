<?php

namespace App\Console\Commands;

use App\Models\Concierge;
use App\Models\User;
use Illuminate\Console\Command;

class SetupDemoUser extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vip:setup-demo-user';

    /**
     * The console command description.
     */
    protected $description = 'Set up demo user and concierge for VIP session fallbacks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up demo user for VIP sessions...');

        // Create or find demo user
        $demoUser = User::query()->firstOrCreate(['email' => 'demo@primavip.co'], [
            'first_name' => 'Demo',
            'last_name' => 'Concierge',
            'email' => 'demo@primavip.co',
            'password' => bcrypt('demo-password-not-used'),
            'email_verified_at' => now(),
        ]);

        if ($demoUser->wasRecentlyCreated) {
            $this->info('✅ Created demo user: '.$demoUser->email);
        } else {
            $this->info('✅ Demo user already exists: '.$demoUser->email);
        }

        // Create or find demo concierge
        $demoConcierge = Concierge::query()->firstOrCreate(['user_id' => $demoUser->id], [
            'user_id' => $demoUser->id,
            'hotel_name' => 'Demo Hotel',
            'phone' => '+1234567890',
            'is_active' => true,
        ]);

        if ($demoConcierge->wasRecentlyCreated) {
            $this->info('✅ Created demo concierge: '.$demoConcierge->hotel_name);
        } else {
            $this->info('✅ Demo concierge already exists: '.$demoConcierge->hotel_name);
        }

        $this->info('');
        $this->info('Demo setup complete!');
        $this->info('Demo User ID: '.$demoUser->id);
        $this->info('Demo Concierge ID: '.$demoConcierge->id);
        $this->info('');
        $this->info('Invalid VIP codes will now fall back to this demo user.');

        return Command::SUCCESS;
    }
}
