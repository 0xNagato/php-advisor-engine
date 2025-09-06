<?php

namespace App\Console\Commands;

use App\Actions\QrCode\GenerateQrCodeWithLogo;
use App\Models\QrCode;
use AshAllenDesign\ShortURL\Models\ShortURL;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegenerateMissingQrCodeFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-missing-qr-code-files {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate missing QR code image files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $generateQrCode = app(GenerateQrCodeWithLogo::class);

        $this->info('Checking for QR codes with missing files...');

        $qrCodes = QrCode::whereNotNull('qr_code_path')
            ->whereNotNull('short_url_id')
            ->limit($limit)
            ->get();

        $regenerated = 0;
        $missing = 0;

        $this->withProgressBar($qrCodes, function (QrCode $qrCode) use ($generateQrCode, &$regenerated, &$missing) {
            // Check if the file exists
            if (! Storage::disk('public')->exists($qrCode->qr_code_path)) {
                $missing++;

                // Get the short URL
                $shortUrl = ShortURL::find($qrCode->short_url_id);
                if ($shortUrl) {
                    try {
                        // Regenerate the QR code
                        $qrCodeData = $generateQrCode->handle(
                            $shortUrl->default_short_url,
                            (string) $qrCode->id
                        );

                        // Update the path
                        $qrCode->update(['qr_code_path' => $qrCodeData['svgPath']]);
                        $regenerated++;
                    } catch (\Exception $e) {
                        $this->error("\nFailed to regenerate QR code {$qrCode->id}: {$e->getMessage()}");
                    }
                }
            }
        });

        $this->newLine();
        $this->info("Found {$missing} missing QR code files");
        $this->info("Successfully regenerated {$regenerated} QR code files");

        return Command::SUCCESS;
    }
}
