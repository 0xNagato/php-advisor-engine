<?php

namespace App\Console\Commands;

use App\Models\QrCode;
use Illuminate\Console\Command;

class UpdateUnassignedQrCodeDestinations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-unassigned-qr-code-destinations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update destination URLs for QR codes without assigned concierges to point to invitation form';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating QR codes without assigned concierges...');

        // Find QR codes without concierges that don't already point to the unassigned route
        $qrCodes = QrCode::whereNull('concierge_id')
            ->whereNotNull('short_url_id')
            ->with('shortUrlModel')
            ->get()
            ->filter(function ($qrCode) {
                return $qrCode->shortUrlModel &&
                    ! str_contains($qrCode->shortUrlModel->destination_url, 'qr.unassigned') &&
                    ! str_contains($qrCode->shortUrlModel->destination_url, 'v.booking');
            });

        $updated = 0;

        foreach ($qrCodes as $qrCode) {
            $newDestination = route('qr.unassigned', ['qrCode' => $qrCode->id]);

            $qrCode->shortUrlModel->update([
                'destination_url' => $newDestination,
            ]);

            $updated++;
            $this->line("Updated QR code {$qrCode->id} ({$qrCode->url_key})");
        }

        $this->info("Updated {$updated} QR codes to point to invitation form");

        return Command::SUCCESS;
    }
}
