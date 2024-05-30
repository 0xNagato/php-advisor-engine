<?php

namespace App\Console\Commands;

use App\Data\RestaurantContactData;
use App\Models\Restaurant;
use App\Models\User;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Console\Command;

class UpdatePhoneNumbersForDevelopment extends Command
{
    use FormatsPhoneNumber;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phone:update {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_ENV') !== 'local') {
            $this->info('This command can only be run in the local environment.');

            return Command::FAILURE;
        }

        $phone = $this->getInternationalFormattedPhoneNumber($this->argument('phone'));
        $this->components->info('Updating phone number to: '.$phone);

        User::query()->update(['phone' => $phone]);

        $restaurants = Restaurant::all();
        foreach ($restaurants as $restaurant) {
            /** @var \Spatie\LaravelData\DataCollection<\App\Data\RestaurantContactData> */
            $contacts = $restaurant->contacts;

            $contacts->each(function (RestaurantContactData $contact) use ($phone) {
                $contact->contact_phone = $phone;
            });

            $restaurant->save();
        }

        $this->components->info('Phone numbers updated successfully.');

        return Command::SUCCESS;
    }
}
