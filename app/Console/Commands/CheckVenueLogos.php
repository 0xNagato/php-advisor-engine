<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CheckVenueLogos extends Command
{
    protected $signature = 'venues:check-logos';

    protected $description = 'Check all venue logos for valid URLs and responses';

    public function handle(): void
    {
        $venues = Venue::all();
        $headers = ['ID', 'Name', 'URL', 'Status'];
        $rows = [];

        $this->info("Checking {$venues->count()} venue logos...");
        $bar = $this->output->createProgressBar($venues->count());

        foreach ($venues as $venue) {
            $url = $venue->logo_path
                ? Storage::disk('do')->url($venue->logo_path)
                : null;

            if (! $url) {
                $rows[] = [$venue->id, $venue->name, 'No logo path set', 'N/A'];
                $bar->advance();

                continue;
            }

            try {
                $response = Http::get($url);
                $status = $response->status();
                $statusText = match ($status) {
                    200 => "<fg=green>$status OK</>",
                    404 => "<fg=red>$status Not Found</>",
                    403 => "<fg=yellow>$status Forbidden</>",
                    default => "<fg=red>$status Error</>"
                };
            } catch (Exception $e) {
                $status = 'Error';
                $statusText = "<fg=red>Failed: {$e->getMessage()}</>";
            }

            $rows[] = [
                $venue->id,
                $venue->name,
                $url,
                $statusText,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table($headers, $rows);

        // Summary
        $this->newLine();
        $totalChecked = count($rows);
        $successful = count(array_filter($rows, fn ($row) => str_contains($row[3], '200')));
        $failed = $totalChecked - $successful;

        $this->info('Summary:');
        $this->line("Total Checked: $totalChecked");
        $this->line("Successful (200): $successful");
        $this->line("Failed: $failed");
    }
}
