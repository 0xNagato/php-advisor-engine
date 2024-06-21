<?php

namespace App\Livewire\Restaurant;

use App\Data\RestaurantContactData;
use App\Models\Restaurant;
use App\Notifications\Restaurant\RestaurantContactLoginSMS;
use App\Traits\FormatsPhoneNumber;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use libphonenumber\PhoneNumberType;
use Livewire\Component;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class RestaurantContactLogin extends Component implements HasForms
{
    use FormatsPhoneNumber, InteractsWithForms;

    public ?string $phone = '';

    public bool $linkSent = false;

    public string $message = 'If your number is associated with a restaurant you will receive a secure link via SMS';

    protected ?string $heading = 'Restaurant Login';

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
                    type: PhoneNumberType::MOBILE,
                    lenient: true,
                )
                ->validationMessages([
                    'validation.phone' => 'The phone number is invalid.',
                ])
                ->required(),
        ];
    }

    public function create(): void
    {
        $phone = $this->form->getState()['phone'];

        $restaurant = Restaurant::query()->whereJsonContains('contacts', [
            'contact_phone' => $phone,
        ])->first();

        if (blank($restaurant)) {
            $this->linkSent = true;

            return;
        }

        $url = URL::temporarySignedRoute(
            'restaurant.contact.bookings',
            now()->addMinutes(30),
            [
                'restaurant' => $restaurant['id'],
            ]
        );

        $user = collect($restaurant->contacts->items())
            ->filter(fn (RestaurantContactData $contact) => $contact->contact_phone === $phone)
            ->first();

        /* @var RestaurantContactData $user */
        $user->notify(new RestaurantContactLoginSMS(url: $url));

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
        return view('livewire.restaurant.contact-login');
    }
}
