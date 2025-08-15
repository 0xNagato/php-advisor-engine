<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BookingsReport extends Command
{
    protected $signature = 'report:bookings {limit : Number of bookings} {region? : Region id or name} {--output=}';

    protected $description = 'Export recent confirmed bookings to CSV with platform revenue.';

    public function handle(): int
    {
        $limit = (int) $this->argument('limit');
        $regionInput = $this->argument('region');
        $output = $this->option('output');

        $tz = 'America/New_York';

        // Normalize region (accept id or name, case-insensitive)
        $regionId = null;
        if ($regionInput) {
            $regionInput = (string) $regionInput;
            $region = Region::query()->firstWhere('id', strtolower($regionInput))
                ?? Region::query()->firstWhere('name', ucfirst(strtolower($regionInput)));
            $regionId = $region?->id;
            if ($regionInput && ! $regionId) {
                $this->warn("Region '{$regionInput}' not found; exporting across all regions.");
            }
        }

        $query = Booking::query()
            ->with(['venue', 'concierge.user'])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->when($regionId, fn ($q) => $q->whereHas('venue', fn (Builder $vq) => $vq->where('region', $regionId)))
            ->orderByDesc('created_at')
            ->limit($limit);

        /** @var Collection<int, Booking> $bookings */
        $bookings = $query->get();

        $rows = $bookings->map(function (Booking $b) use ($tz) {
            $created = Carbon::parse($b->created_at)->timezone($tz)->format('M j, Y g:ia');
            $bookingDate = Carbon::parse($b->booking_at)->format('M j, Y g:ia');

            $regionName = '';
            if ($b->venue && $b->venue->region) {
                $r = Region::query()->find($b->venue->region);
                $regionName = $r ? $r->name : $b->venue->region;
            }

            $revenueCents = (int) $b->gross_revenue; // accessor returns cents
            // Always divide by 100 since everything is stored as cents
            $revenue = number_format($revenueCents / 100, 2, '.', '');

            return [
                'Booking ID' => $b->id,
                'Created' => $created,
                'Booking Date' => $bookingDate,
                'Guest name' => $b->guest_name,
                'Guest email' => $b->guest_email,
                'Guest phone' => $b->guest_phone, // Use raw E164 format
                'Guest count' => $b->guest_count,
                'Venue' => $b->venue?->name,
                'Region' => $regionName,
                'Concierge' => $b->concierge?->user?->name,
                'Hotel/Company' => $b->concierge?->hotel_name,
                'Status' => $b->status->label(),
                'Prime Status' => $b->prime_status_label,
                'Gross Revenue' => $revenue,
                'Currency' => $b->currency,
            ];
        });

        if (! $output) {
            $suffix = $regionId ?: 'all';
            $timestamp = now('America/New_York')->format('Ymd_His');
            $dir = 'reports';
            if (! Storage::disk('local')->exists($dir)) {
                Storage::disk('local')->makeDirectory($dir);
            }
            $output = storage_path("app/{$dir}/bookings-{$suffix}-{$timestamp}.csv");
        }

        // Write CSV directly to ensure proper formatting
        $file = fopen($output, 'w');

        // Write headers
        fputcsv($file, [
            'Booking ID',
            'Created',
            'Booking Date',
            'Guest name',
            'Guest email',
            'Guest phone',
            'Guest count',
            'Venue',
            'Region',
            'Concierge',
            'Hotel/Company',
            'Status',
            'Prime Status',
            'Gross Revenue',
            'Currency',
        ]);

        // Write data rows
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        $this->info('Exported '.$bookings->count()." rows (plus total) to: {$output}");

        return self::SUCCESS;
    }
}
