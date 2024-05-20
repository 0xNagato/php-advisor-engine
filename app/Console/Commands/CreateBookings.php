<?php

namespace App\Console\Commands;

use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleWithBooking;
use App\Services\SalesTaxService;
use Illuminate\Console\Command;

class CreateBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:create {schedule : The ID of the schedule} {number : The number of bookings to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a number of bookings for a specific schedule';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scheduleId = $this->argument('schedule');
        $number = $this->argument('number');

        $schedule = ScheduleWithBooking::find($scheduleId);

        if (! $schedule) {
            $this->error("Schedule with id $scheduleId not found.");

            return 1;
        }

        $partners = Partner::all();

        for ($i = 0; $i < $number; $i++) {
            $booking = $schedule->bookings()->create([
                'concierge_id' => Concierge::inRandomOrder()->first()->id,
                'guest_first_name' => 'John',
                'guest_last_name' => 'Doe',
                'guest_email' => 'johndoe@fake.com',
                'guest_phone' => '1234567890',
                'guest_count' => $schedule->party_size,
                'booking_at' => $schedule->booking_at,
                'partner_concierge_id' => $partners->random()->id,
                'partner_restaurant_id' => $partners->random()->id,
                'status' => 'confirmed',
            ]);

            $taxData = app(SalesTaxService::class)->calculateTax('miami', $booking->total_fee);
            $totalWithTaxInCents = $booking->total_fee + $taxData->amountInCents;

            $booking->update([
                'tax' => $taxData->tax,
                'tax_amount_in_cents' => $taxData->amountInCents,
                'city' => $taxData->region,
                'total_with_tax_in_cents' => $totalWithTaxInCents,
                'confirmed_at' => $schedule->booking_at,
            ]);
        }

        $this->info("Successfully created $number bookings for schedule $scheduleId.");

        return 0;
    }
}
