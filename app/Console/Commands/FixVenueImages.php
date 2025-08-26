<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Illuminate\Console\Command;

class FixVenueImages extends Command
{
    protected $signature = 'venues:fix-images
                            {--dry-run : Show the changes without saving them (summary only unless --details)}
                            {--details : List each changed image in format original => updated}
                            {--only= : Comma separated venue IDs to restrict processing}
                            {--chunk=200 : Chunk size for processing venues}
                            {--limit= : Maximum number of venues to process (after filtering)}';

    protected $description = 'Detect and fix duplicated bucket host prefixes in venue images JSON array field.';

    private const string BUCKET_HOST = 'https://prima-bucket.nyc3.digitaloceanspaces.com/';

    private const string HOST_PATTERN = '/^(https:\\/\\/prima-bucket\.nyc3\.digitaloceanspaces\.com\\/)+/';

    // Runtime state
    private bool $dryRun = false;

    private bool $showDetails = false;

    private ?int $limit = null;

    private int $chunkSize = 200;

    private array $onlyIds = [];

    private int $processed = 0;

    private int $updatedVenues = 0;

    private int $modifiedImages = 0;

    private array $details = [];

    public function handle(): int
    {
        $this->readOptions();
        $query = $this->baseQuery();
        $this->info("Found {$this->candidateCount($query)} venue(s) with non-empty images to inspect.");
        $this->processInChunks($query);
        $this->outputResults();

        return 0;
    }

    // --- Option / Query Setup -------------------------------------------------
    private function readOptions(): void
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $this->showDetails = (bool) $this->option('details');
        $this->chunkSize = (int) $this->option('chunk');
        $this->limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($this->dryRun) {
            $this->info('Running in DRY RUN mode. No database updates will be committed.');
        }

        $only = $this->option('only');
        if ($only) {
            $this->onlyIds = collect(explode(',', $only))
                ->filter(fn ($v) => is_numeric($v))
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
            if (empty($this->onlyIds)) {
                $this->error('No valid IDs provided in --only option.');
                exit(1);
            }
        }
    }

    private function baseQuery()
    {
        $query = Venue::query()
            ->whereNotNull('images')
            ->whereRaw("images::text <> '[]'")
            ->orderBy('id');

        if (! empty($this->onlyIds)) {
            $query->whereIn('id', $this->onlyIds);
        }

        return $query;
    }

    private function candidateCount($query): int
    {
        $count = (clone $query)->count();
        if ($this->limit !== null) {
            return min($count, $this->limit);
        }

        return $count;
    }

    // --- Processing -----------------------------------------------------------
    private function processInChunks($query): void
    {
        $stop = false;
        $query->chunk($this->chunkSize, function ($venues) use (&$stop) {
            if ($stop) {
                return false;
            }
            foreach ($venues as $venue) {
                if ($this->limit !== null && $this->processed >= $this->limit) {
                    $stop = true;
                    break;
                }
                $this->processVenue($venue);
                $this->processed++;
            }
        });
    }

    private function processVenue(Venue $venue): void
    {
        $raw = $venue->getRawOriginal('images');
        if (blank($raw)) {
            return;
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($decoded)) {
            return;
        }

        $original = $decoded;
        $cleaned = [];
        $changed = false;

        foreach ($original as $img) {
            $cleanedImg = $this->normalizeImage($img, $imgChanged);
            $changed = $changed || $imgChanged;
            $cleaned[] = $cleanedImg;
        }

        if (! $changed) {
            return; // nothing to do
        }

        $this->updatedVenues++;
        $diffCount = $this->countDifferences($original, $cleaned);
        $this->modifiedImages += $diffCount;
        $this->details[] = [
            'venue' => $venue->name,
            'pairs' => $this->buildChangedPairs($original, $cleaned),
        ];

        if (! $this->dryRun) {
            // store relative paths (strip host)
            $venue->images = array_map(fn ($url) => $this->toRelative($url), $cleaned);
            $venue->save();
        }
    }

    // --- Image Helpers --------------------------------------------------------
    private function normalizeImage($image, ?bool &$changed = false): string
    {
        $changed = false;
        if (! is_string($image) || $image === '') {
            return $image;
        }
        if (! str_contains($image, self::BUCKET_HOST)) {
            return $image; // already relative or different host
        }

        $occurrences = substr_count($image, self::BUCKET_HOST);
        $clean = preg_replace(self::HOST_PATTERN, self::BUCKET_HOST, $image);
        if ($clean !== $image || $occurrences > 1) {
            $changed = true;
        }

        return $clean;
    }

    private function toRelative(string $url): string
    {
        return str_starts_with($url, self::BUCKET_HOST)
            ? substr($url, strlen(self::BUCKET_HOST))
            : $url;
    }

    private function countDifferences(array $before, array $after): int
    {
        $count = 0;
        $max = max(count($before), count($after));
        for ($i = 0; $i < $max; $i++) {
            if (($before[$i] ?? null) !== ($after[$i] ?? null)) {
                $count++;
            }
        }

        return $count;
    }

    private function buildChangedPairs(array $before, array $after): array
    {
        $pairs = [];
        $max = max(count($before), count($after));
        for ($i = 0; $i < $max; $i++) {
            $b = $before[$i] ?? null;
            $a = $after[$i] ?? null;
            if ($b !== $a) {
                $pairs[] = ['before' => $b, 'after' => $a];
            }
        }

        return $pairs;
    }

    // --- Output ----------------------------------------------------------------
    private function outputResults(): void
    {
        if ($this->updatedVenues === 0) {
            $this->info('No venues required image fixes.');
        } else {
            if ($this->showDetails) {
                $this->info('Details:');
                foreach ($this->details as $detail) {
                    if (empty($detail['pairs'])) {
                        continue;
                    }
                    $this->line($detail['venue']);
                    foreach ($detail['pairs'] as $pair) {
                        $this->line('  '.$pair['before'].' => '.$pair['after']);
                    }
                    $this->newLine();
                }
            } elseif ($this->dryRun) {
                $this->info('Dry run (use --details to list each changed image).');
            }
        }

        $this->info('Summary');
        $this->table(['Metric', 'Count'], [
            ['Processed venues', $this->processed],
            ['Updated venues', $this->updatedVenues],
            ['Modified images', $this->modifiedImages],
            ['Dry run', $this->dryRun ? 'yes' : 'no'],
        ]);
    }
}
