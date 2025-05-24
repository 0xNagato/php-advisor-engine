<?php

namespace App\Console\Commands;

use App\Actions\QrCode\GenerateQrCodeWithLogo;
use App\Models\QrCode;
use AshAllenDesign\ShortURL\Models\ShortURL;
use Exception;
use Illuminate\Console\Command;
use Storage;

class RegenerateQrCodes extends Command
{
    protected $signature = 'qr:regenerate-bulk {--limit=0 : Limit to number of QRs to regenerate (0 = all)}';

    protected $description = 'Regenerate bulk QR codes with the new format (no ribbon)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $qrCodesQuery = QrCode::query()
            ->whereNotNull('short_url_id')
            ->orderBy('id');

        if ($limit > 0) {
            $qrCodesQuery->limit($limit);
        }

        $qrCodes = $qrCodesQuery->get();
        $total = $qrCodes->count();

        if ($total === 0) {
            $this->info('No QR codes found to regenerate.');

            return 0;
        }

        $this->info("Regenerating {$total} QR codes...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $regenerated = 0;
        $errors = 0;
        $generateQrCode = app(GenerateQrCodeWithLogo::class);

        foreach ($qrCodes as $qrCode) {
            try {
                // Get the short URL
                $shortUrl = ShortURL::query()->find($qrCode->short_url_id);

                if (! $shortUrl) {
                    $this->error("Short URL not found for QR code {$qrCode->id}");
                    $errors++;
                    $bar->advance();

                    continue;
                }

                // Store old path to delete later
                $oldPath = $qrCode->qr_code_path;

                // Generate new QR code
                $qrCodeData = $generateQrCode->handle(
                    $shortUrl->default_short_url,
                    (string) $qrCode->id
                );

                // Update QR code
                $qrCode->update([
                    'qr_code_path' => $qrCodeData['svgPath'],
                ]);

                // Delete old file if different
                if ($oldPath && $oldPath !== $qrCodeData['svgPath']) {
                    Storage::disk('public')->delete($oldPath);
                }

                $regenerated++;
            } catch (Exception $e) {
                $this->error("Error regenerating QR code {$qrCode->id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Regenerated {$regenerated} QR codes successfully.");

        if ($errors > 0) {
            $this->warn("{$errors} errors occurred during regeneration.");

            return 1;
        }

        return 0;
    }
}
