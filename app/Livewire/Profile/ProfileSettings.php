<?php

namespace App\Livewire\Profile;

use App\Data\NotificationPreferencesData;
use App\Models\Region;
use App\Models\User;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class ProfileSettings extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.profile-settings';

    protected static ?string $pollingInterval = null;

    public ?array $data = [];

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        /**
         * @var User $user
         */
        $user = auth()->user();

        $this->form->fill([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'timezone' => $user->timezone ?? 'UTC',
            'notification_regions' => $user->notification_regions ?? ['miami'],
            'preferences' => $user->preferences?->toArray() ?? NotificationPreferencesData::from([
                'database' => true,
            ])->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            // FileUpload::make('profile_photo_path')
            //     ->label('Profile Photo')
            //     ->disk('do')
            //     ->imageEditor()
            //     ->circleCropper()
            //     ->visibility('public')
            //     ->directory('profile-photos')
            //     ->optimize('webp')
            //     ->moveFiles()
            //     ->hidden(fn () => auth()->user()->hasActiveRole('venue')),
            Grid::make()
                ->schema([
                    TextInput::make('first_name')
                        ->required()
                        ->label('First Name'),
                    TextInput::make('last_name')
                        ->required()
                        ->label('Last Name'),
                ])
                ->columns([
                    'default' => 2,
                    'sm' => 2,
                    'md' => 2,
                    'lg' => 2,
                    'xl' => 2,
                ]),
            TextInput::make('email')
                ->required()
                ->label('Email')
                ->unique('users', ignorable: auth()->user())
                ->email(),
            PhoneInput::make('phone')
                ->unique('users', ignorable: auth()->user())
                ->required()
                ->onlyCountries(config('app.countries'))
                ->displayNumberFormat(PhoneInputNumberType::E164)
                ->disallowDropdown()
                ->validateFor(
                    country: config('app.countries'),
                    lenient: true,
                )
                ->label('Phone'),
            TimezoneSelect::make('timezone')
                ->searchable()
                ->selectablePlaceholder(false)
                ->required(),
            CheckboxList::make('notification_regions')
                ->label(new HtmlString('
                    <div class="block w-full mb-1">Notification Preferences</div>
                    <div class="block w-full mb-2 font-normal text-gray-500">Select the regions where you plan on creating bookings so we can notify you about new venues and experiences.</div>
                '))
                ->options(function () {
                    $regions = Region::all();
                    $orderedNames = ['Miami', 'Los Angeles', 'Ibiza', 'New York'];

                    return $regions->sortBy(function ($region) use ($orderedNames) {
                        $index = array_search($region->name, $orderedNames);

                        return $index === false ? PHP_INT_MAX : $index;
                    })
                        ->pluck('name', 'id');
                })
                ->columns([
                    'default' => 2,
                    'sm' => 2,
                    'md' => 2,
                    'lg' => 2,
                    'xl' => 2,
                ])
                ->gridDirection('row')
                ->columnSpan('full'),
            Fieldset::make('preferences')
                ->label('Notification Preferences')
                ->statePath('preferences')
                ->columns(1)
                ->schema([
                    Toggle::make('mail')
                        ->label('Email')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value && ! $get('sms') && ! $get('whatsapp')) {
                                    $fail('At least one notification method must be enabled.');
                                }
                            },
                        ])
                        ->inline(),

                    Toggle::make('sms')
                        ->label('SMS')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value && ! $get('mail') && ! $get('whatsapp')) {
                                    $fail('At least one notification method must be enabled.');
                                }
                            },
                        ])
                        ->inline(),

                    Toggle::make('whatsapp')
                        ->label('Whatsapp')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value && ! $get('mail') && ! $get('sms')) {
                                    $fail('At least one notification method must be enabled.');
                                }
                            },
                        ])
                        ->inline(),

                    Toggle::make('database')
                        ->label('Application')
                        ->inline()
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value && ! $get('mail') && ! $get('sms') && ! $get('whatsapp')) {
                                    $fail('At least one notification method must be enabled.');
                                }
                            },
                        ]),
                ]),
        ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();

        $profilePhotoPath = $data['profile_photo_path'] ?? null;

        if ($profilePhotoPath) {
            Storage::disk('do')->setVisibility($profilePhotoPath, 'public');
        }

        $user->update($data);

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }
}
