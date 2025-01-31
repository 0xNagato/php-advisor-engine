<?php

namespace App\Livewire\Venue;

use App\Data\VenueContactData;
use App\Models\Venue;
use App\Notifications\Venue\VenueContactLoginSMS;
use App\Traits\FormatsPhoneNumber;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class VenueContactLogin extends Component implements HasForms
{
    use FormatsPhoneNumber, InteractsWithForms;

    public ?string $phone = '';

    public bool $linkSent = false;

    public string $message = 'If your number is associated with a venue you will receive a secure link via SMS';

    protected ?string $heading = 'Venue Login';

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function getSubHeading(): ?string
    {
        return 'Enter your phone number to receive a booking link.';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getFormSchema(): array
    {
        return [
            PhoneInput::make('phone')
                ->live()
                ->label('Phone Number')
                ->displayNumberFormat(PhoneInputNumberType::E164)
                ->disallowDropdown()
                ->placeholder('Phone Number')
                ->onlyCountries(config('app.countries'))
                ->validateFor(
                    country: config('app.countries'),
                    lenient: true,
                )
                ->required(),
        ];
    }

    public function create(): void
    {
        $phone = $this->form->getState()['phone'];

        $venue = Venue::query()->whereJsonContains('contacts', [
            'contact_phone' => $phone,
        ])->first();

        if (blank($venue)) {
            $this->linkSent = true;

            return;
        }

        $url = URL::temporarySignedRoute(
            'venue.contact.bookings',
            now()->addMinutes(30),
            [
                'venue' => $venue['id'],
            ]
        );

        $user = collect($venue->contacts->items())
            ->filter(fn (VenueContactData $contact) => $contact->contact_phone === $phone)
            ->first();

        /* @var VenueContactData $user */
        $user->notify(new VenueContactLoginSMS(url: $url));

        $this->linkSent = true;
    }

    public function getFormActions(): array
    {
        return [
            Action::make('createLink')
                ->label('Send Login Link')
                ->color('indigo')
                ->submit('create'),
        ];
    }

    public function areFormActionsSticky(): bool
    {
        return false;
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    public function render(): View
    {
        return view('livewire.venue.contact-login');
    }
}
