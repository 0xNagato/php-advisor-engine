<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Contracts\Database\Query\Builder;

// ini_set('max_execution_time', 0);
ini_set('memory_limit', '5G');

use App\Enums\VenueStatus;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MiamiVenueSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = fopen(base_path('database/seeders/miami.csv'), 'r');

        // Skip the header row
        fgetcsv($csvFile);

        DB::disableQueryLog();

        while (($data = fgetcsv($csvFile, 2000, ',')) !== false) {
            try {
                DB::beginTransaction();

                $venueName = $data[0];
                $openingHours = $this->formatOpeningHours($data);

                $partner = Partner::query()->inRandomOrder()->first();
                $email = 'venue@'.Str::slug($venueName).'-miami.com';

                $user = User::factory([
                    'first_name' => 'Venue',
                    'last_name' => $venueName,
                    'partner_referral_id' => $partner?->id,
                    'email' => $email,
                ])->create();

                $venue = Venue::factory([
                    'name' => $venueName,
                    'status' => VenueStatus::ACTIVE,
                    'region' => 'miami',
                    'user_id' => $user->id,
                ])->create();

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
                Log::info("Venue created: $venueName");
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("Error creating venue: $venueName. Error: ".$e->getMessage());
            }
        }

        fclose($csvFile);
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
                ->chunk(200, function (Builder $schedules) use ($openTime, $closeTime) {
                    foreach ($schedules as $schedule) {
                        $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);
                        $isAvailable = $openTime && $closeTime &&
                                       $startTime->format('H:i') >= $openTime->format('H:i') &&
                                       $startTime->format('H:i') < $closeTime->format('H:i');

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
