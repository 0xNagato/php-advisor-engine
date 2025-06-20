<?php

namespace App\Console\Commands;

use App\Models\VipCode;
use App\Services\VipCodeService;
use Illuminate\Console\Command;

class CreateVipSessionToken extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vip:create-session-token
                            {vip_code : The VIP code to create a session for}
                            {--demo : Create a demo session instead of using a VIP code}
                            {--expires=24 : Hours until the token expires (default: 24)}
                            {--search= : Search VIP codes by concierge name, hotel, or email}
                            {--list : List all VIP codes with details}';

    /**
     * The console command description.
     */
    protected $description = 'Create a VIP session token for testing or administrative purposes';

    /**
     * Execute the console command.
     */
    public function handle(VipCodeService $vipCodeService): int
    {
        $vipCode = $this->argument('vip_code');
        $isDemo = $this->option('demo');
        $expiresHours = (int) $this->option('expires');
        $searchTerm = $this->option('search');
        $listAll = $this->option('list');

        // Handle search functionality
        if ($searchTerm) {
            return $this->searchVipCodes($searchTerm);
        }

        // Handle list functionality
        if ($listAll) {
            return $this->listAllVipCodes();
        }

        $this->info('Creating VIP session token...');
        $this->line("VIP Code: {$vipCode}");
        $this->line('Demo Mode: '.($isDemo ? 'Yes' : 'No'));
        $this->line("Expires in: {$expiresHours} hours");

        if ($isDemo) {
            // Create demo session
            $this->info('Creating demo session...');

            try {
                $sessionData = $vipCodeService->createDemoSession();

                $this->displaySessionInfo($sessionData, true);

                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error('Failed to create demo session: '.$e->getMessage());
                $this->line('Make sure the demo user exists by running: php artisan vip:setup-demo-user');

                return Command::FAILURE;
            }
        }

        // Validate VIP code exists
        $vipCodeModel = VipCode::where('code', $vipCode)->first();

        if (! $vipCodeModel) {
            $this->error("VIP code '{$vipCode}' not found in the database.");
            $this->showAvailableVipCodes();

            return Command::FAILURE;
        }

        if (! $vipCodeModel->is_active) {
            $this->warn("VIP code '{$vipCode}' is inactive.");
            if (! $this->confirm('Do you want to create a session anyway?')) {
                return Command::FAILURE;
            }
        }

        // Create VIP session
        $this->info('Creating VIP session...');

        try {
            $sessionData = $vipCodeService->createVipSession($vipCode);

            if (! $sessionData) {
                $this->error('Failed to create VIP session.');

                return Command::FAILURE;
            }

            $this->displaySessionInfo($sessionData, false);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create VIP session: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Search VIP codes by concierge information
     */
    private function searchVipCodes(string $searchTerm): int
    {
        $this->info("Searching VIP codes for: '{$searchTerm}'");
        $this->newLine();

        $vipCodes = VipCode::with('concierge.user')
            ->whereHas('concierge.user', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->orWhereHas('concierge', function ($query) use ($searchTerm) {
                $query->where('hotel_name', 'like', "%{$searchTerm}%");
            })
            ->orWhere('code', 'like', "%{$searchTerm}%")
            ->orderBy('code')
            ->get();

        if ($vipCodes->isEmpty()) {
            $this->warn("No VIP codes found matching '{$searchTerm}'");
            $this->line('Try searching with different terms or use --list to see all codes.');

            return Command::FAILURE;
        }

        $this->info("Found {$vipCodes->count()} VIP code(s):");
        $this->newLine();

        $this->table(
            ['Code', 'Concierge', 'Hotel', 'Email', 'Active', 'Created'],
            $vipCodes->map(fn ($code) => [
                $code->code,
                $code->concierge->user->name ?? 'Unknown',
                $code->concierge->hotel_name ?? 'Unknown',
                $code->concierge->user->email ?? 'Unknown',
                $code->is_active ? 'Yes' : 'No',
                $code->created_at->format('M j, Y'),
            ])
        );

        $this->newLine();
        $this->line('To create a session token, use:');
        $this->line('  php artisan vip:create-session-token CODE_NAME');

        return Command::SUCCESS;
    }

    /**
     * List all VIP codes with details
     */
    private function listAllVipCodes(): int
    {
        $this->info('All VIP Codes in Database:');
        $this->newLine();

        $vipCodes = VipCode::with('concierge.user')
            ->orderBy('code')
            ->get();

        if ($vipCodes->isEmpty()) {
            $this->warn('No VIP codes found in the database.');

            return Command::FAILURE;
        }

        $this->table(
            ['Code', 'Concierge', 'Hotel', 'Email', 'Active', 'Created'],
            $vipCodes->map(fn ($code) => [
                $code->code,
                $code->concierge->user->name ?? 'Unknown',
                $code->concierge->hotel_name ?? 'Unknown',
                $code->concierge->user->email ?? 'Unknown',
                $code->is_active ? 'Yes' : 'No',
                $code->created_at->format('M j, Y'),
            ])
        );

        $this->newLine();
        $this->line('To create a session token, use:');
        $this->line('  php artisan vip:create-session-token CODE_NAME');
        $this->newLine();
        $this->line('To search for specific codes, use:');
        $this->line('  php artisan vip:create-session-token SEARCH --search="term"');

        return Command::SUCCESS;
    }

    /**
     * Show available VIP codes when code not found
     */
    private function showAvailableVipCodes(): void
    {
        $this->line('Available VIP codes:');

        $vipCodes = VipCode::with('concierge.user')
            ->orderBy('code')
            ->limit(10)
            ->get(['id', 'code', 'concierge_id', 'is_active']);

        if ($vipCodes->isEmpty()) {
            $this->line('No VIP codes found in the database.');
        } else {
            $this->table(
                ['ID', 'Code', 'Concierge', 'Active'],
                $vipCodes->map(fn ($code) => [
                    $code->id,
                    $code->code,
                    $code->concierge->user->name ?? 'Unknown',
                    $code->is_active ? 'Yes' : 'No',
                ])
            );

            if (VipCode::count() > 10) {
                $this->line('Showing first 10 results. Use --list to see all codes or --search to find specific ones.');
            }
        }

        $this->newLine();
        $this->line('Search options:');
        $this->line('  --search="name"     Search by concierge name, hotel, or email');
        $this->line('  --list              Show all VIP codes with details');
        $this->line('  --demo              Create a demo session instead');
    }

    /**
     * Display session information in a formatted way
     */
    private function displaySessionInfo(array $sessionData, bool $isDemo): void
    {
        $this->newLine();
        $this->info('✅ VIP Session Token Created Successfully!');
        $this->newLine();

        // Display token information
        $this->line('Session Token:');
        $this->line('<fg=yellow>'.$sessionData['token'].'</>');
        $this->newLine();

        // Display expiration
        $this->line('Expires At:');
        $this->line($sessionData['expires_at']);
        $this->newLine();

        if ($isDemo) {
            $this->line('Mode: <fg=blue>Demo Session</>');
            $this->line('Message: '.($sessionData['demo_message'] ?? 'Demo mode active'));
        } else {
            $this->line('Mode: <fg=green>VIP Session</>');
            $this->line('VIP Code: '.$sessionData['vip_code']->code);
            $this->line('Concierge: '.$sessionData['vip_code']->concierge->user->name);
            $this->line('Hotel: '.$sessionData['vip_code']->concierge->hotel_name);
        }

        $this->newLine();
        $this->line('Usage Examples:');
        $this->line('  curl -H "Authorization: Bearer '.$sessionData['token'].'" '.config('app.url').'/api/me');
        $this->line('  curl -H "Authorization: Bearer '.$sessionData['token'].'" '.config('app.url').'/api/venues');

        $this->newLine();
        $this->line('⚠️  This token will expire in 24 hours and should only be used for testing.');
    }
}
