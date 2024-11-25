<?php

namespace App\Console\Commands;

use App\Actions\Venue\ParseVenueScheduleWithClaude;
use App\Data\VenueContactData;
use App\Enums\VenueStatus;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueScheduleService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParseVenuePrimeSchedule extends Command
{
    protected $signature = 'venue:parse-schedules {--dry-run}';

    protected $description = 'Parse venue schedules using Claude';

    public function __construct(
        private readonly VenueScheduleService $venueScheduleService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Enter the schedule text (press Ctrl+D or Cmd+D when finished):');
        $lines = [];
        while (! feof(STDIN)) {
            $lines[] = fgets(STDIN);
        }

        $scheduleText = implode('', $lines);
        if (blank(trim($scheduleText))) {
            $this->error('No input provided.');

            return 1;
        }

        $inputHandle = fopen('/dev/tty', 'r');

        try {
            $this->output->write("\n🤔 Analyzing schedule... ");
            $parsedData = ParseVenueScheduleWithClaude::run($scheduleText);

            // Save response to JSON file
            $timestamp = now()->format('Y-m-d_H-i-s');
            $debugPath = storage_path('logs/venue-schedules/');
            if (! file_exists($debugPath)) {
                mkdir($debugPath, 0755, true);
            }

            $debugFile = $debugPath."schedule_response_{$timestamp}.json";
            file_put_contents($debugFile, json_encode([
                'input' => $scheduleText,
                'response' => $parsedData,
            ], JSON_PRETTY_PRINT));

            $this->info("\nDebug file saved to: {$debugFile}");

            $this->output->write("\r".str_repeat(' ', 70)."\r");

            foreach ($parsedData['venues'] as $venueData) {
                $venue = Venue::query()->where('name', $venueData['name'])->first();

                $this->info("\nSchedule Preview for {$venueData['name']}:");
                $this->displaySchedule($venueData['schedule']);

                if (! $venue) {
                    $this->info("\nVenue '{$venueData['name']}' not found. Would you like to create it? (yes/no) [yes]:");
                    $create = strtolower(trim(fgets($inputHandle)) ?: 'yes');

                    if ($create !== 'yes' && $create !== 'y') {
                        $this->info("Skipped creating venue: {$venueData['name']}");

                        continue;
                    }

                    $venue = $this->createVenue($venueData['name'], $inputHandle, $venueData);
                }

                if (! $this->option('dry-run')) {
                    $this->info("\nWould you like to apply these changes for {$venue->name}? (yes/no) [no]:");
                    $apply = strtolower(trim(fgets($inputHandle)) ?: 'no');

                    if ($apply === 'yes' || $apply === 'y') {
                        $this->venueScheduleService->updateSchedule($venue, $venueData['schedule']);
                        $this->info("✅ Schedule updated for {$venue->name}");
                    } else {
                        $this->info("Skipped updates for {$venue->name}");
                    }
                }
            }
        } catch (Exception $e) {
            $this->error("\n❌ Error: ".$e->getMessage());
            fclose($inputHandle);

            return 1;
        }

        fclose($inputHandle);

        return 0;
    }

    private function displaySchedule(array $schedule): void
    {
        $headers = ['Day', 'Status', 'Operating Hours', 'Prime Time Slots'];
        $rows = [];
        foreach ($schedule as $day => $data) {
            $rows[] = [
                ucfirst($day),
                $data['is_open'] ? '<fg=green>Open</>' : '<fg=red>Closed</>',
                $data['is_open'] ? "{$data['open_time']} - {$data['close_time']}" : 'Closed',
                $data['is_open'] ?
                    (blank($data['prime_slots']) ? 'All Non-Prime' :
                    collect($data['prime_slots'])->map(fn ($slot) => "{$slot['start']} - {$slot['end']}")->implode(', ')) :
                    'N/A',
            ];
        }
        $this->table($headers, $rows);
    }

    private function createVenue(string $name, $inputHandle, array $venueData): Venue
    {
        $this->info("\nCreating new venue user...");

        while (true) {
            $this->info('Enter email:');
            $email = trim(fgets($inputHandle));

            // Check for existing user with this email
            if (User::query()->where('email', $email)->exists()) {
                $this->error('A user with this email already exists. Please use a different email.');

                continue;
            }

            break;
        }

        $this->info('Enter first name:');
        $firstName = trim(fgets($inputHandle));

        $this->info('Enter last name:');
        $lastName = trim(fgets($inputHandle));

        $this->info('Enter phone number (e.g. +16473823326):');
        $phone = trim(fgets($inputHandle));

        DB::beginTransaction();
        try {
            $housePartner = Partner::query()
                ->whereHas('user', function (Builder $query) {
                    $query->where('email', 'house.partner@primavip.co');
                })
                ->first();

            /** @var User $user */
            $user = User::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'password' => bcrypt(Str::random()),
                'partner_referral_id' => $housePartner?->id,
                'secured_at' => now(),
            ]);

            // Assign venue role
            $user->assignRole('venue');

            // Create venue
            $venue = new Venue;
            $venue->name = $name;
            $venue->status = VenueStatus::PENDING;
            $venue->user_id = $user->id;
            $venue->contact_phone = $phone;
            $venue->primary_contact_name = "$firstName $lastName";
            $venue->party_sizes = [
                'Special Request' => 0,
                '2' => 2,
                '4' => 4,
                '6' => 6,
                '8' => 8,
            ];

            // Add primary contact to contacts array
            $venue->contacts = collect([
                new VenueContactData(
                    contact_name: "$firstName $lastName",
                    contact_phone: $phone,
                    use_for_reservations: true,
                ),
            ]);

            $venue->save();

            Referral::query()->create([
                'referrer_id' => $housePartner?->user->id,
                'user_id' => $user->id,
                'email' => $email,
                'secured_at' => now(),
                'type' => 'venue',
                'referrer_type' => 'partner',
            ]);

            $this->venueScheduleService->createDefaultScheduleTemplates($venue, $venueData['schedule']);

            DB::commit();
            $this->info("✅ Created new venue: {$venue->name}");

            return $venue;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
