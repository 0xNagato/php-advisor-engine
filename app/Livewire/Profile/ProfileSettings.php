<?php

namespace App\Livewire\Profile;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use libphonenumber\PhoneNumberType;
use Livewire\Component;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

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
                ->live(onBlur: true)
                ->afterStateUpdated(function (Component $livewire) {
                    $livewire->dispatch('open-modal','check2FACode');
                })
                ->email(),
            PhoneInput::make('phone')
                ->required()
                ->onlyCountries(config('app.countries'))
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

    public function check2FACode(): Action
    {
        return Action::make('check2FA')
            ->label('Verify Email Change')
            ->modalHeading('Verify Your Identity')
            ->form([
                TextInput::make('code')
                    ->required()
                    ->label('2FA Code'),
            ])
            ->action(function (array $data): void {
                dd('2FA code is valid');
                // Here you would check the 2FA code validity
                // For example, compare it with the code stored in the database for the user
                // If valid, update the email and other user details
                // If not, return an error message
            });
    }

    public function save(): void
    {
        $data = $this->form->getState();
        //check if the email field is modified

        if ($data['email'] !== auth()->user()->email) {
            $this->livewire->mountFormComponentAction('check2FACode');
            dd('2FA code is required');
        }

        // Assuming the profile photo is stored as a file
        $profilePhotoPath = $data['profile_photo_path'];

        // Make the profile photo file public
        Storage::disk('do')->setVisibility($profilePhotoPath, 'public');

        // Update the user's profile photo file path
        $data['profile_photo_path'] = $profilePhotoPath;

        auth()->user()->update($data);

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }
}
