<?php

namespace App\Console\Commands;

use App\Data\Venue\SaveReservationHoursData;
use App\Enums\VenueStatus;
use App\Models\Venue;
use App\Services\ReservationHoursService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ParseVenuePrimeSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venue:parse-schedules {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse venue schedules using Claude';

    private const MAX_RETRIES = 3;

    private const CLAUDE_PROMPT = <<<'EOT'
        You are a parser for restaurant schedules. Given the following schedule text, extract:
        1. Venue names
        2. Operating days (if specified)
        3. Operating hours (assume 5:00 PM - 10:30 PM if not specified)
        4. Prime time slots per day

        Important time slot rules:
        - All times must align to 30-minute increments (e.g., :00 or :30)
        - If an end time doesn't align to a 30-minute increment, round UP to the next increment
        - Example: 8:45 PM should become 9:00 PM

        For each venue, specify which days are open/closed and the prime time slots.
        If a time range mentions "close", use "22:30" as the end time.
        If "All hours are non-prime" is specified, return an empty prime_slots array.

        Format as JSON:
        {
            "venues": [
                {
                    "name": "string",
                    "schedule": {
                        "monday": {
                            "is_open": boolean,
                            "open_time": "17:00",
                            "close_time": "22:30",
                            "prime_slots": [
                                {
                                    "start": "HH:00/30",
                                    "end": "HH:00/30"
                                }
                            ]
                        }
                        // ... repeat for all days
                    }
                }
            ]
        }

        Input text:
        {text}
    EOT;

    private function parseWithClaude(string $text, int $attempt = 1): array
    {
        $this->output->write("\n🤔 Analyzing schedule (attempt {$attempt}/3)... ");

        try {
            $response = Http::withHeaders([
                'anthropic-version' => '2023-06-01',
                'x-api-key' => config('services.anthropic.api_key'),
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-sonnet-20240229',
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => str_replace('{text}', $text, self::CLAUDE_PROMPT),
                    ],
                ],
            ]);

            // Clear the spinner line
            $this->output->write("\r".str_repeat(' ', 70)."\r");

            $content = $response['content'][0]['text'] ?? null;
            if (! $content) {
                throw new \Exception('Empty response from Claude');
            }

            // Extract JSON from the markdown code block if present
            if (str_contains($content, '```json')) {
                preg_match('/```json\n(.*)\n```/s', $content, $matches);
                $content = $matches[1] ?? $content;
            }

            $parsedData = json_decode($content, true);
            if (! $parsedData || ! isset($parsedData['venues'])) {
                throw new \Exception('Invalid JSON structure');
            }

            return $parsedData;
        } catch (\Exception $e) {
            if ($attempt < self::MAX_RETRIES) {
                $this->warn("\nAttempt {$attempt} failed: {$e->getMessage()}");
                $this->info('Retrying...');

                return $this->parseWithClaude($text, $attempt + 1);
            }
            throw new \Exception("Failed after {$attempt} attempts: ".$e->getMessage());
        }
    }

    private function exportToCsv(array $venues, string $path): void
    {
        $rows = [];
        foreach ($venues as $venue) {
            foreach ($venue['schedule'] as $day => $schedule) {
                $rows[] = [
                    'venue' => $venue['name'],
                    'day' => ucfirst($day),
                    'status' => $schedule['is_open'] ? 'Open' : 'Closed',
                    'hours' => $schedule['is_open'] ? "{$schedule['open_time']} - {$schedule['close_time']}" : 'Closed',
                    'prime_slots' => $schedule['is_open'] ?
                        (empty($schedule['prime_slots']) ? 'All Non-Prime' :
                        collect($schedule['prime_slots'])->map(fn ($slot) => "{$slot['start']} - {$slot['end']}")->implode(', ')) :
                        'N/A',
                ];
            }
        }

        $fp = fopen($path, 'w');
        fputcsv($fp, ['Venue', 'Day', 'Status', 'Operating Hours', 'Prime Time Slots']);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    private function updateVenueSchedule(Venue $venue, array $schedule): void
    {
        DB::transaction(function () use ($venue, $schedule) {
            // First update the open days and hours using ReservationHoursService
            $selectedDays = [];
            $startTimes = [];
            $endTimes = [];

            foreach ($schedule as $day => $daySchedule) {
                $selectedDays[$day] = $daySchedule['is_open'];
                if ($daySchedule['is_open']) {
                    $startTimes[$day] = $daySchedule['open_time'];
                    $endTimes[$day] = $daySchedule['close_time'];
                }
            }

            // Use the service to save hours
            $data = new SaveReservationHoursData(
                venue: $venue,
                selectedDays: $selectedDays,
                startTimes: $startTimes,
                endTimes: $endTimes
            );

            app(ReservationHoursService::class)->saveHours($data);

            // Then handle prime time slots separately
            foreach ($schedule as $dayOfWeek => $daySchedule) {
                $venue->scheduleTemplates()
                    ->where('day_of_week', $dayOfWeek)
                    ->chunk(200, function ($templates) use ($daySchedule) {
                        foreach ($templates as $template) {
                            $startTime = Carbon::createFromFormat('H:i:s', $template->start_time);
                            $isPrimeTime = false;

                            if ($daySchedule['is_open'] && ! empty($daySchedule['prime_slots'])) {
                                foreach ($daySchedule['prime_slots'] as $slot) {
                                    $slotStart = Carbon::createFromFormat('H:i', $slot['start']);
                                    $slotEnd = Carbon::createFromFormat('H:i', $slot['end']);

                                    if ($startTime->format('H:i') >= $slotStart->format('H:i') &&
                                        $startTime->format('H:i') <= $slotEnd->format('H:i')) {
                                        $isPrimeTime = true;
                                        break;
                                    }
                                }
                            }

                            $template->update([
                                'prime_time' => $isPrimeTime,
                                'available_tables' => $daySchedule['is_open'] ? Venue::DEFAULT_TABLES : 0,
                            ]);
                        }
                    });
            }
        });
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Enter the schedule text (press Ctrl+D or Cmd+D when finished):');
        $lines = [];
        while (! feof(STDIN)) {
            $lines[] = fgets(STDIN);
        }

        // Create a new input handle for prompts
        $inputHandle = fopen('/dev/tty', 'r');

        $scheduleText = implode('', $lines);
        if (empty(trim($scheduleText))) {
            $this->error('No input provided.');
            fclose($inputHandle);

            return 1;
        }

        try {
            $parsedData = $this->parseWithClaude($scheduleText);

            // Always export and show preview
            $csvPath = storage_path('app/prime-schedule-preview.csv');
            $this->exportToCsv($parsedData['venues'], $csvPath);
            $this->info("\n✅ CSV preview saved to: {$csvPath}");

            foreach ($parsedData['venues'] as $venueData) {
                $venue = Venue::where('name', $venueData['name'])->first();

                // Show preview table first
                $this->info("\nSchedule Preview for {$venueData['name']}:");
                $headers = ['Day', 'Status', 'Operating Hours', 'Prime Time Slots'];
                $rows = [];
                foreach ($venueData['schedule'] as $day => $schedule) {
                    $rows[] = [
                        ucfirst($day),
                        $schedule['is_open'] ? '<fg=green>Open</>' : '<fg=red>Closed</>',
                        $schedule['is_open'] ? "{$schedule['open_time']} - {$schedule['close_time']}" : 'Closed',
                        $schedule['is_open'] ? (empty($schedule['prime_slots']) ? 'All Non-Prime' :
                            collect($schedule['prime_slots'])->map(fn ($slot) => "{$slot['start']} - {$slot['end']}")->implode(', ')) : 'N/A',
                    ];
                }
                $this->table($headers, $rows);

                if (! $venue) {
                    // Manual prompt handling
                    $this->info("\nVenue '{$venueData['name']}' not found. Would you like to create it? (yes/no) [yes]:");
                    $create = strtolower(trim(fgets($inputHandle)) ?: 'yes');

                    if ($create !== 'yes' && $create !== 'y') {
                        $this->info("Skipped creating venue: {$venueData['name']}");

                        continue;
                    }

                    $venue = new Venue;
                    $venue->name = $venueData['name'];
                    $venue->status = VenueStatus::PENDING;
                    $venue->save();

                    $this->createDefaultScheduleTemplates($venue);
                    $this->info("✅ Created new venue: {$venue->name}");
                }

                if (! $this->option('dry-run')) {
                    // Manual prompt handling
                    $this->info("\nWould you like to apply these changes for {$venue->name}? (yes/no) [no]:");
                    $apply = strtolower(trim(fgets($inputHandle)) ?: 'no');

                    if ($apply === 'yes' || $apply === 'y') {
                        $this->updateVenueSchedule($venue, $venueData['schedule']);
                        $this->info("✅ Schedule updated for {$venue->name}");
                    } else {
                        $this->info("Skipped updates for {$venue->name}");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("\n❌ Error: ".$e->getMessage());
            fclose($inputHandle);

            return 1;
        }

        fclose($inputHandle);

        return 0;
    }
}
