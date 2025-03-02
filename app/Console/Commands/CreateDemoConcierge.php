<?php

namespace App\Console\Commands;

use App\Models\Concierge;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class CreateDemoConcierge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prima:create-demo-concierge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a demo concierge for a selected venue group';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all venue groups
        $venueGroups = VenueGroup::all();

        if ($venueGroups->isEmpty()) {
            error('No venue groups found in the system.');

            return 1;
        }

        // Prepare venue group choices with concierge status
        $choices = [];
        foreach ($venueGroups as $venueGroup) {
            $hasConcierge = Concierge::query()->where('venue_group_id', $venueGroup->id)->exists();
            $status = $hasConcierge ? ' [Has concierge]' : ' [No concierge]';
            $choices[$venueGroup->id] = $venueGroup->name.$status;
        }

        // Ask user to select a venue group using Laravel Prompts
        $selectedGroupId = select(
            label: 'Select a venue group:',
            options: $choices
        );

        // Get the selected venue group
        $venueGroup = VenueGroup::query()->find($selectedGroupId);

        // Check if this venue group already has a concierge
        $existingConcierge = Concierge::query()->where('venue_group_id', $venueGroup->id)->first();
        if ($existingConcierge) {
            if (! confirm(
                label: 'This venue group already has a concierge. Do you want to create another one?',
                default: false
            )) {
                info('Operation cancelled.');

                return 0;
            }
        }

        // Generate email and password
        $timestamp = time();
        $defaultEmail = "demo.concierge.{$timestamp}@prima.test";
        $defaultPassword = 'DemoC0nc!erge2023';

        // Allow customization of email and password using Laravel Prompts
        $email = text(
            label: 'Enter email for the concierge:',
            placeholder: 'E.g. concierge@example.com',
            default: $defaultEmail,
            required: true
        );

        $password = password(
            label: 'Enter password for the concierge:',
            placeholder: 'Leave empty for default password'
        ) ?: $defaultPassword;

        // Get venue IDs for this venue group
        $venueIds = Venue::query()->where('venue_group_id', $venueGroup->id)
            ->pluck('id')
            ->toArray();

        // Create the concierge
        try {
            DB::transaction(function () use ($venueGroup, $email, $password, $venueIds) {
                // Create user
                $user = new User;
                $user->first_name = 'Demo';
                $user->last_name = 'Concierge';
                $user->email = $email;
                $user->password = Hash::make($password);
                $user->email_verified_at = now();
                $user->save();

                // Assign concierge role
                $user->assignRole('concierge');

                // Create concierge
                $concierge = new Concierge;
                $concierge->user_id = $user->id;
                $concierge->venue_group_id = $venueGroup->id;
                $concierge->allowed_venue_ids = $venueIds;
                $concierge->hotel_name = "Demo Hotel for {$venueGroup->name}";
                $concierge->save();
            });

            info('Demo concierge created successfully!');

            // Display login details in a table
            table(
                headers: ['Venue Group', 'Email', 'Password'],
                rows: [
                    [$venueGroup->name, $email, $password],
                ]
            );

            return 0;
        } catch (Exception $e) {
            error('Failed to create concierge: '.$e->getMessage());

            return 1;
        }
    }
}
