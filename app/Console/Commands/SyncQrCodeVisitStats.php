<?php

namespace App\Console\Commands;

use App\Models\QrCode;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use Illuminate\Console\Command;

class SyncQrCodeVisitStats extends Command
{
    protected $signature = 'qr-codes:sync-stats';

    protected $description = 'Sync visit statistics from short URLs to bulk QR codes';

    public function handle()
    {
        $this->info('Syncing QR code visit statistics...');

        $qrCodes = QrCode::query()->whereNotNull('short_url_id')->get();
        $bar = $this->output->createProgressBar(count($qrCodes));
        $bar->start();

        $updatedCount = 0;

        foreach ($qrCodes as $qrCode) {
            // Get the last visit timestamp and count of visits
            $stats = ShortURLVisit::query()->where('short_url_id', $qrCode->short_url_id)
                ->selectRaw('MAX(visited_at) as last_visited, COUNT(*) as total_visits')
                ->first();

            if ($stats && $stats->total_visits > 0) {
                $qrCode->scan_count = $stats->total_visits;
                $qrCode->last_scanned_at = $stats->last_visited;
                $qrCode->save();
                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Updated statistics for {$updatedCount} QR codes.");

        return Command::SUCCESS;
    }
}
