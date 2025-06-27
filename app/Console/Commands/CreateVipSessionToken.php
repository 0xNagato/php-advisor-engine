<?php

namespace App\Console\Commands;

use App\Models\VipCode;
use App\Services\VipCodeService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class CreateVipSessionToken extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vip:create-session-token
                            {vip_code : The VIP code to create a session for}
                            {--search= : Search VIP codes by concierge name, hotel, or email}
                            {--list : List all VIP codes with details}';

    /**
     * The console command description.
     */
    protected $description = 'Create a VIP session token for testing or administrative purposes. Uses fallback code if invalid code is provided.';

    /**
     * Execute the console command.
     */
    public function handle(VipCodeService $vipCodeService): int
    {
        $vipCode = $this->argument('vip_code');
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

        $fallbackCode = config('app.vip.fallback_code');
        if ($fallbackCode) {
            $this->line("Fallback Code: {$fallbackCode} (used if main code is invalid)");
        }

        // Create VIP session (will use fallback code if original is invalid)
        $this->info('Creating VIP session...');

        try {
            $sessionData = $vipCodeService->createVipSession($vipCode);

            if (! $sessionData) {
                $this->error('Failed to create VIP session. Both the provided code and fallback code are invalid or inactive.');
                $this->showAvailableVipCodes();

                return Command::FAILURE;
            }

            // Check if fallback code was used
            $usedFallback = $sessionData['vip_code']->code !== strtoupper($vipCode);
            if ($usedFallback) {
                $this->warn("Original code '{$vipCode}' not found or inactive. Used fallback code '{$sessionData['vip_code']->code}'.");
            }

            $this->displaySessionInfo($sessionData, $usedFallback);

            return Command::SUCCESS;

        } catch (Exception $e) {
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
            ->whereHas('concierge.user', function (Builder $query) use ($searchTerm) {
                $query->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->orWhereHas('concierge', function (Builder $query) use ($searchTerm) {
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

            if (VipCode::query()->count() > 10) {
                $this->line('Showing first 10 results. Use --list to see all codes or --search to find specific ones.');
            }
        }

        $this->newLine();
        $this->line('Search options:');
        $this->line('  --search="name"     Search by concierge name, hotel, or email');
        $this->line('  --list              Show all VIP codes with details');

        $fallbackCode = config('app.vip.fallback_code');
        if ($fallbackCode) {
            $this->newLine();
            $this->line('Note: If an invalid code is provided, the system will automatically');
            $this->line("      use the fallback code '{$fallbackCode}' if available.");
        }
    }

    /**
     * Display session information in a formatted way
     */
    private function displaySessionInfo(array $sessionData, bool $usedFallback): void
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

        if ($usedFallback) {
            $this->line('Mode: <fg=yellow>VIP Session (Fallback Code Used)</>');
        } else {
            $this->line('Mode: <fg=green>VIP Session</>');
        }

        $this->line('VIP Code: '.$sessionData['vip_code']->code);
        $this->line('Concierge: '.$sessionData['vip_code']->concierge->user->name);
        $this->line('Hotel: '.$sessionData['vip_code']->concierge->hotel_name);

        $this->newLine();
        $this->line('Usage Examples:');
        $this->line('  curl -H "Authorization: Bearer '.$sessionData['token'].'" '.config('app.url').'/api/me');
        $this->line('  curl -H "Authorization: Bearer '.$sessionData['token'].'" '.config('app.url').'/api/venues');

        $sessionDurationHours = config('app.vip.session_duration_hours', 24);
        $this->newLine();
        $this->line("⚠️  This token will expire in {$sessionDurationHours} hours and should only be used for testing.");
    }
}
