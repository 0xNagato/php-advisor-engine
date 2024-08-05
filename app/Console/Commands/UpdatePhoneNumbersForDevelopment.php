<?php

namespace App\Console\Commands;

use App\Data\VenueContactData;
use App\Models\User;
use App\Models\Venue;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Console\Command;
use Spatie\LaravelData\DataCollection;

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

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $phone = $this->getInternationalFormattedPhoneNumber($this->argument('phone'));
        $this->components->info('Updating phone number to: '.$phone);

        User::query()->update(['phone' => $phone]);

        $venues = Venue::all();
        foreach ($venues as $venue) {
            /** @var DataCollection<VenueContactData> */
            $contacts = $venue->contacts;

            $contacts->each(function (VenueContactData $contact) use ($phone) {
                $contact->contact_phone = $phone;
            });

            $venue->save();
        }

        $this->components->info('Phone numbers updated successfully.');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
