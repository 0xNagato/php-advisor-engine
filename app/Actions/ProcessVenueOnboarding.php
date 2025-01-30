<?php

namespace App\Actions;

use App\Data\VenueContactData;
use App\Enums\VenueStatus;
use App\Models\Partner;
use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use App\Models\VenueOnboarding;
use App\Models\VenueOnboardingLocation;
use App\Notifications\WelcomeVenueManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessVenueOnboarding
{
    public function execute(
        VenueOnboarding $onboarding,
        User $processedBy,
        string $notes,
        array $venueDefaults = []
    ): void {
        throw_if(User::query()->where('email', $onboarding->email)->exists(), ValidationException::withMessages([
            'email' => "A user with the email {$onboarding->email} already exists.",
        ]));

        DB::transaction(function () use ($onboarding, $processedBy, $notes, $venueDefaults) {
            /** @var Partner|null $partner */
            $partner = User::query()->find($onboarding->partner_id)?->partner;

            // Create the primary manager user
            $managerUser = User::query()->create([
                'first_name' => $onboarding->first_name,
                'last_name' => $onboarding->last_name,
                'email' => $onboarding->email,
                'phone' => $onboarding->phone,
                'password' => bcrypt(Str::random(16)),
                'partner_referral_id' => $partner?->id,
            ]);

            $managerUser->assignRole('venue_manager');

            // Create the venue group
            $venueGroup = VenueGroup::query()->create([
                'name' => $onboarding->company_name,
                'primary_manager_id' => $managerUser->id,
            ]);

            // Add manager to venue group managers with allowed venues
            $venues = [];
            $venueData = [];
            foreach ($onboarding->locations as $location) {
                $venue = Venue::query()->create([
                    'name' => $location->name,
                    'venue_group_id' => $venueGroup->id,
                    'user_id' => $managerUser->id,
                    'region' => $location->region,
                    'timezone' => Region::getTimezoneForRegion($location->region),
                    'prime_hours' => $location->prime_hours ?? [],
                    'use_non_prime_incentive' => $location->use_non_prime_incentive,
                    'non_prime_per_diem' => $location->non_prime_per_diem,
                    'logo_path' => $location->logo_path,
                    'status' => VenueStatus::PENDING,
                    'payout_venue' => $venueDefaults['payout_venue'] ?? 60,
                    'booking_fee' => $venueDefaults['booking_fee'] ?? 200,
                    'contact_phone' => $onboarding->phone,
                    'primary_contact_name' => $managerUser->name,
                    'contacts' => [
                        VenueContactData::from([
                            'contact_name' => $managerUser->name,
                            'contact_phone' => $onboarding->phone,
                            'use_for_reservations' => true,
                        ])->toArray(),
                    ],
                ]);

                // Update the onboarding location with the created venue ID
                $location->update([
                    'created_venue_id' => $venue->id,
                ]);

                // Set up booking hours first (this affects available slots)
                $this->setupBookingHours($venue, $location);

                // Then set up prime time slots
                $this->setupPrimeTimeSlots($venue, $location);

                $venues[] = $venue->id;
                $venueData[] = [
                    'name' => $venue->name,
                ];
            }

            // Attach manager with allowed venues
            $venueGroup->managers()->attach($managerUser->id, [
                'allowed_venue_ids' => json_encode($venues),
                'current_venue_id' => $venues[0] ?? null,
            ]);

            // Send welcome email to venue manager
            $managerUser->notify(new WelcomeVenueManager($managerUser, $venueData));

            $onboarding->markAsProcessed($processedBy, $notes);
        });
    }

    private function setupBookingHours(Venue $venue, VenueOnboardingLocation $location): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // First set all slots to unavailable
        $venue->scheduleTemplates()->update(['is_available' => false]);

        foreach ($daysOfWeek as $dayOfWeek) {
            $bookingHours = $location->booking_hours[$dayOfWeek] ?? null;

            if (! $bookingHours || $bookingHours['closed']) {
                continue;
            }

            $startTime = Carbon::createFromFormat('H:i:s', $bookingHours['start']);
            $endTime = Carbon::createFromFormat('H:i:s', $bookingHours['end']);

            // Make slots available within booking hours
            $venue->scheduleTemplates()
                ->where('day_of_week', $dayOfWeek)
                ->get()
                ->each(function ($template) use ($startTime, $endTime) {
                    $slotTime = Carbon::createFromFormat('H:i:s', $template->start_time);

                    if ($slotTime->greaterThanOrEqualTo($startTime) &&
                        $slotTime->lessThan($endTime)) {
                        $template->update(['is_available' => true]);
                    }
                });
        }
    }

    private function setupPrimeTimeSlots(Venue $venue, VenueOnboardingLocation $location): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // First set all slots to non-prime
        $venue->scheduleTemplates()
            ->where('is_available', true)  // Only update available slots
            ->update(['prime_time' => false]);

        foreach ($daysOfWeek as $dayOfWeek) {
            $primeHours = $location->prime_hours[$dayOfWeek] ?? [];

            if (! is_array($primeHours) || blank($primeHours)) {
                continue;
            }

            // Get all the prime time slots for this day
            $primeTimeSlots = array_keys(array_filter($primeHours, fn ($value) => $value === true));

            foreach ($primeTimeSlots as $timeSlot) {
                $venue->scheduleTemplates()
                    ->where('day_of_week', $dayOfWeek)
                    ->where('start_time', $timeSlot)
                    ->where('is_available', true)  // Only update available slots
                    ->update(['prime_time' => true]);
            }
        }
    }
}
