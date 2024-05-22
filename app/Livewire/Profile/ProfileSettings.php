<?php

namespace App\Livewire\Profile;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use libphonenumber\PhoneNumberType;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class ProfileSettings extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.profile-settings';

    protected static bool $isLazy = false;

    public ?array $data = [];

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $this->form->fill([
            'first_name' => auth()->user()->first_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'timezone' => auth()->user()->timezone ?? 'UTC',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('profile_photo_path')
                ->label('Profile Photo')
                ->disk('do')
                ->imageEditor()
                ->circleCropper()
                ->visibility('public')
                ->directory('profile-photos')
                ->optimize('webp')
                ->moveFiles()
                ->hidden(function () {
                    return auth()->user()->hasRole('restaurant');
                }),
            TextInput::make('first_name')
                ->required()
                ->label('First Name'),
            TextInput::make('last_name')
                ->required()
                ->label('Last Name'),
            TextInput::make('email')
                ->required()
                ->label('Email')
                ->unique('users', ignorable: auth()->user())
                ->email(),
            PhoneInput::make('phone')
                ->required()
                ->onlyCountries(config('app.countries'))
                ->displayNumberFormat(PhoneInputNumberType::E164)
                ->disallowDropdown()
                ->validateFor(
                    country: config('app.countries'),
                    type: PhoneNumberType::MOBILE,
                    lenient: true,
                )
                ->label('Phone'),
            TimezoneSelect::make('timezone')
                ->searchable()
                ->selectablePlaceholder(false)
                ->required(),

        ])
            ->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();

        $user = auth()->user();

        if (! $this->requiresTwoFactorAuthentication($user, $data)) {
            $this->updateProfileWithoutTwoFactor($data, $user);
        } else {
            $this->updateProfileWithTwoFactor($data, $user);
        }
    }

    protected function updateProfileWithTwoFactor(array $data, $user)
    {
        session()->put('pending-data.'.$user->id, $data);

        return redirect()->route('filament.admin.pages.two-factor-code', ['redirect' => 'filament.admin.pages.my-settings']);
    }

    protected function updateProfileWithoutTwoFactor(array $data, $user)
    {
        $profilePhotoPath = $data['profile_photo_path'];
        Storage::disk('do')->setVisibility($profilePhotoPath, 'public');

        $user->update($data);

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }

    // if user has not verified the device and the user details have changed
    protected function requiresTwoFactorAuthentication($user, array $data): bool
    {
        $sessionKey = 'usercode.'.$user->id;

        return ! $this->deviceIsVerified($sessionKey) && $this->userDetailsChanged($user, $data);
    }

    protected function deviceIsVerified($sessionKey): bool
    {
        return session()->has($sessionKey) && session($sessionKey) === true;
    }

    protected function userDetailsChanged($user, array $data): bool
    {
        return $data['email'] !== $user->email || $data['phone'] !== $user->phone;
    }
}
