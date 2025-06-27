<?php

namespace App\Livewire;

use App\Data\VenueContactData;
use App\Models\Venue;
use App\Models\VenueOnboarding;
use App\Notifications\VenueAgreementAccepted;
use App\Notifications\VenueAgreementCopy;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class VenueAgreementForm extends Component
{
    public VenueOnboarding $onboarding;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public bool $agreement_accepted = false;

    public ?string $successMessage = null;

    public function mount(VenueOnboarding $onboarding): void
    {
        $this->onboarding = $onboarding;

        // Don't pre-fill form with existing data
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'agreement_accepted' => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'agreement_accepted.accepted' => 'You must accept the agreement to proceed.',
        ];
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function downloadAgreement(): void
    {
        $this->validate();

        $this->updateOnboarding();

        $this->dispatch('download-agreement', encryptedId: encrypt($this->onboarding->id));
    }

    public function emailAgreement(): void
    {
        $this->validate();

        $this->updateOnboarding();

        // Send email with the agreement attached
        Notification::route('mail', $this->email)
            ->notify(new VenueAgreementCopy($this->onboarding));

        $this->successMessage = 'Agreement has been sent to your email.';
    }

    private function updateOnboarding(): void
    {
        // Check if this is the first time the agreement is being accepted
        // by checking if agreement_accepted_at is null in the database
        $wasNeverAccepted = is_null($this->onboarding->fresh()->agreement_accepted_at);

        $this->onboarding->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'agreement_accepted' => true,
            'agreement_accepted_at' => now(),
        ]);

        // Update any venues connected to this onboarding
        $this->updateConnectedVenues();

        // Send notification to PRIMA team only if this is the first time accepting the agreement
        if ($wasNeverAccepted) {
            $this->sendAgreementAcceptedNotification();
        }
    }

    /**
     * Updates any venues that were created from this onboarding with the collected contact information
     */
    private function updateConnectedVenues(): void
    {
        // Get all venues connected to this onboarding
        $venueIds = $this->onboarding->locations()
            ->whereNotNull('created_venue_id')
            ->pluck('created_venue_id')
            ->toArray();

        if (blank($venueIds)) {
            return;
        }

        $fullName = $this->first_name.' '.$this->last_name;

        // Update all connected venues
        $venues = Venue::query()->whereIn('id', $venueIds)->get();

        foreach ($venues as $venue) {
            // Update venue contact fields
            $venue->primary_contact_name = $fullName;
            $venue->contact_phone = $this->phone;

            // Update venue contacts array
            $contacts = $venue->contacts?->toArray() ?? [];

            // If there are existing contacts, update them with the new information
            $contactsUpdated = false;

            if (filled($contacts)) {
                foreach ($contacts as $index => $contact) {
                    // Only update contacts that match the old onboarding info or are main contacts
                    if (isset($contact['use_for_reservations']) && $contact['use_for_reservations']) {
                        $contacts[$index]['contact_name'] = $fullName;
                        $contacts[$index]['contact_phone'] = $this->phone;
                        if (! isset($contacts[$index]['email']) || blank($contacts[$index]['email'])) {
                            $contacts[$index]['email'] = $this->email;
                        }
                        $contactsUpdated = true;
                    }
                }
            }

            // If no contacts were updated, add a new one
            if (! $contactsUpdated) {
                $contacts[] = VenueContactData::from([
                    'contact_name' => $fullName,
                    'contact_phone' => $this->phone,
                    'email' => $this->email,
                    'use_for_reservations' => true,
                ])->toArray();
            }

            $venue->contacts = $contacts;
            $venue->save();
        }
    }

    /**
     * Send notification to PRIMA team when agreement is accepted
     */
    private function sendAgreementAcceptedNotification(): void
    {
        // Send to the specified PRIMA team email addresses
        $primaEmails = [
            'kevin@primavip.co',
            'prima+agreement@primavip.co',
            'aj@primavip.co',
        ];

        foreach ($primaEmails as $email) {
            Notification::route('mail', $email)
                ->notify(new VenueAgreementAccepted($this->onboarding));
        }
    }

    public function render()
    {
        return view('livewire.venue-agreement-form');
    }
}
