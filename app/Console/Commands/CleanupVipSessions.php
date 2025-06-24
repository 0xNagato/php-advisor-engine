<?php

namespace App\Console\Commands;

use App\Models\VipSession;
use App\Services\VipCodeService;
use Illuminate\Console\Command;

class CleanupVipSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vip:cleanup-sessions {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired VIP session tokens';

    public function __construct(
        private readonly VipCodeService $vipCodeService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting VIP session cleanup...');

        if ($this->option('dry-run')) {
            $count = VipSession::query()->where('expires_at', '<', now())->count();
            $this->info("Would delete {$count} expired sessions");

            return self::SUCCESS;
        }

        $deletedCount = $this->vipCodeService->cleanupExpiredSessions();

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} expired VIP sessions");
        } else {
            $this->info('No expired VIP sessions found');
        }

        return self::SUCCESS;
    }
}
