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
                ->disk('do')
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

        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

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
