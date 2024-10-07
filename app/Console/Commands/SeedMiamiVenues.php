<?php

namespace App\Console\Commands;

ini_set('memory_limit', '5G');

use App\Enums\VenueStatus;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SeedMiamiVenues extends Command
{
    protected $signature = 'seed:miami-venues {--use-house-partner : Use house partner instead of random partners}';

    protected $description = 'Seed Miami venues from CSV file';

    private bool $useHousePartner;

    private ?Partner $housePartner;

    public function __construct()
    {
        parent::__construct();
        $this->useHousePartner = false;
        $this->housePartner = null;
    }

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        $this->useHousePartner = $this->option('use-house-partner');

        if ($this->useHousePartner) {
            $this->housePartner = $this->getOrCreateHousePartner();
            if (! $this->housePartner) {
                $this->error('Failed to create or retrieve house partner. Aborting.');

                return 1;
            }
        }

        $csvFile = fopen(base_path('database/seeders/miami.csv'), 'rb');

        if (! $csvFile) {
            $this->error('CSV file not found. Aborting.');

            return 1;
        }

        // Skip the header row
        fgetcsv($csvFile);

        DB::disableQueryLog();

        $bar = $this->output->createProgressBar(count(file(base_path('database/seeders/miami.csv'))) - 1);

        while (($data = fgetcsv($csvFile, 2000)) !== false) {
            try {
                $this->processVenue($data);
                $bar->advance();
            } catch (Throwable $e) {
                $this->error("Error creating venue: $data[0]. Error: ".$e->getMessage());
            }
        }

        fclose($csvFile);
        $bar->finish();
        $this->newLine();
        $this->info('Miami venues seeding completed.');

        return 0;
    }

    /**
     * @throws Throwable
     */
    private function processVenue(array $data): void
    {
        DB::beginTransaction();

        try {
            $venueName = $data[0];
            $openingHours = $this->formatOpeningHours($data);

            $partner = $this->getPartner();
            $email = 'venue@'.Str::slug($venueName).'-miami.com';

            /** @var User $user */
            $user = User::query()->create([
                'first_name' => 'Venue',
                'last_name' => $venueName,
                'partner_referral_id' => $partner?->id,
                'email' => $email,
                'phone' => '+16473823326',
                'password' => bcrypt(Str::random()),
            ]);

            $venue = Venue::query()->create([
                'name' => $venueName,
                'contact_phone' => '+16473823326',
                'primary_contact_name' => 'Andrew Weir',
                'status' => VenueStatus::ACTIVE,
                'region' => 'miami',
                'user_id' => $user->id,
                'party_sizes' => [
                    'Special Request' => 0,
                    '2' => 2,
                    '4' => 4,
                    '6' => 6,
                    '8' => 8,
                ],
            ]);

            $user->assignRole('venue');

            Referral::query()->create([
                'referrer_id' => $partner?->user->id,
                'user_id' => $user->id,
                'email' => $email,
                'secured_at' => now(),
                'type' => 'venue',
                'referrer_type' => 'partner',
            ]);

            $this->updateScheduleTemplates($venue, $openingHours);

            DB::commit();
            $this->info("Venue created: $venueName");
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getPartner(): ?Partner
    {
        if ($this->useHousePartner) {
            return $this->housePartner;
        }

        return Partner::query()->inRandomOrder()->first();
    }

    /**
     * @throws Throwable
     */
    private function getOrCreateHousePartner(): ?Partner
    {
        $housePartnerUser = User::query()->where('email', 'house.partner@primavip.co')->first();

        if (! $housePartnerUser) {
            DB::beginTransaction();
            try {
                $housePartnerUser = User::query()->create([
                    'first_name' => 'House',
                    'last_name' => 'Partner',
                    'email' => 'house.partner@primavip.co',
                    'password' => bcrypt('secure_password_here'),
                ]);

                $housePartner = Partner::query()->create([
                    'user_id' => $housePartnerUser->id,
                    'percentage' => 20,
                ]);

                $housePartnerUser->assignRole('partner');

                DB::commit();
                $this->info('House partner created successfully.');

                return $housePartner;
            } catch (Throwable $e) {
                DB::rollBack();
                $this->error('Failed to create house partner: '.$e->getMessage());

                return null;
            }
        }

        return $housePartnerUser->partner;
    }

    private function formatOpeningHours(array $data): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $openingHours = [];

        for ($i = 0; $i < 7; $i++) {
            $openTime = $data[$i * 2 + 1];
            $closeTime = $data[$i * 2 + 2];

            $openingHours[$days[$i]] = [
                'open' => $openTime === 'CLOSED' ? null : $openTime,
                'close' => $closeTime === 'CLOSED' ? null : $closeTime,
            ];
        }

        return $openingHours;
    }

    private function updateScheduleTemplates(Venue $venue, array $openingHours): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $dayOfWeek) {
            $dayHours = $openingHours[$dayOfWeek];
            $openTime = $dayHours['open'] ? Carbon::createFromFormat('H:i', $dayHours['open']) : null;
            $closeTime = $dayHours['close'] ? Carbon::createFromFormat('H:i', $dayHours['close']) : null;

            $venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->chunk(200, function (Collection $schedules) use ($openTime, $closeTime) {
                    foreach ($schedules as $schedule) {
                        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);
                        $isAvailable = $openTime && $closeTime &&
                                       $startTime?->format('H:i') >= $openTime->format('H:i') &&
                                       $startTime?->format('H:i') <= $closeTime->format('H:i');

                        $schedule->update([
                            'is_available' => $isAvailable,
                            'prime_time' => $isAvailable,
                            'available_tables' => $isAvailable ? Venue::DEFAULT_TABLES : 0,
                        ]);
                    }
                });
        }
    }
}
