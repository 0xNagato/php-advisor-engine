<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\Resources\VenueResource;
use App\Models\Referral;
use App\Models\Region;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Registration')
                    ->icon('heroicon-m-user')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->placeholder('First Name')
                            ->autocomplete(false)
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->placeholder('Last Name')
                            ->autocomplete(false)
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->placeholder('name@domain.com')
                            ->autocomplete(false)
                            ->email()
                            ->unique(User::class, 'email')
                            ->required(),
                        PhoneInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder('Phone Number')
                            ->hint('Used for SMS notifications')
                            ->onlyCountries(config('app.countries'))
                            ->displayNumberFormat(PhoneInputNumberType::E164)
                            ->disallowDropdown()
                            ->validateFor(
                                country: config('app.countries'),
                                lenient: true,
                            )
                            ->initialCountry('US')
                            ->unique(User::class, 'phone')
                            ->required(),
                    ]),
                Section::make('Venue Information')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        TextInput::make('name')
                            ->label('Venue Name')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('logo_path')
                            ->label('Venue Logo')
                            ->disk('do')
                            ->directory(app()->environment().'/venues')
                            ->moveFiles()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(8192)
                            ->imagePreviewHeight('254')
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file, Get $get) {
                                    // Generate slug from the venue name
                                    $venueName = $get('name') ?? null;
                                    if (! $venueName) {
                                        return null;
                                    }
                                    $slug = Str::slug($venueName);

                                    return $slug.'-'.time().'.'.$file->getClientOriginalExtension();
                                }
                            ),
                        Select::make('region')
                            ->placeholder('Select Region')
                            ->options(Region::all()->sortBy('id')->pluck('name', 'id'))
                            ->required(),
                        TextInput::make('primary_contact_name')
                            ->label('Primary Contact Name')
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Primary Contact Phone')
                            ->required()
                            ->onlyCountries(config('app.countries'))
                            ->displayNumberFormat(PhoneInputNumberType::E164)
                            ->disallowDropdown()
                            ->validateFor(
                                country: config('app.countries'),
                                lenient: true,
                            )
                            ->initialCountry('US'),
                        TextInput::make('daily_prime_bookings_cap')
                            ->label('Daily Prime Bookings Cap')
                            ->helperText('Maximum number of prime-time bookings allowed per day. Leave empty for no limit.')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(999),
                        TextInput::make('daily_non_prime_bookings_cap')
                            ->label('Daily Non-Prime Bookings Cap')
                            ->helperText('Maximum number of non-prime-time bookings allowed per day. Leave empty for no limit.')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(999),
                        TextInput::make('cutoff_time')
                            ->label('Cutoff Time')
                            ->type('time')
                            ->helperText('Time after which same-day reservations cannot be made. Leave empty to allow same-day bookings until closing.')
                            ->nullable(),
                    ]),

                Repeater::make('contacts')
                    ->label('Contacts')
                    ->addActionLabel('Add Additional Contact')
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->required()
                            ->onlyCountries(config('app.countries'))
                            ->displayNumberFormat(PhoneInputNumberType::E164)
                            ->disallowDropdown()
                            ->validateFor(
                                country: config('app.countries'),
                                lenient: true,
                            )
                            ->initialCountry('US'),
                        Checkbox::make('use_for_reservations')
                            ->label('Use for Reservations')
                            ->default(true),
                    ]),

                Section::make('Payout Information')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('booking_fee')
                            ->label('Booking Fee')
                            ->prefix(fn (Get $get) => Region::getCurrencySymbolForRegion($get('region') ?? 'miami'))
                            ->default(200)
                            ->numeric()
                            ->required(),
                        TextInput::make('payout_venue')
                            ->label('Payout Venue')
                            ->default(60)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('minimum_spend')
                            ->label('Minimum Spend')
                            ->prefix(fn (Get $get) => Region::getCurrencySymbolForRegion($get('region') ?? 'miami'))
                            ->numeric(),
                        Toggle::make('is_omakase')
                            ->label('Is Omakase')
                            ->live()
                            ->default(false),
                        Textarea::make('omakase_details')
                            ->label('Omakase Details')
                            ->visible(fn (Get $get): bool => $get('is_omakase'))
                            ->nullable(),
                        TextInput::make('omakase_concierge_fee')
                            ->label('Omakase Concierge Fee')
                            ->prefix(fn (Get $get) => Region::getCurrencySymbolForRegion($get('region') ?? 'miami'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => $get('is_omakase'))
                            ->helperText('Flat fee paid to concierge for each Omakase booking')
                            ->required(fn (Get $get): bool => $get('is_omakase')),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $partnerId = auth()->user()->hasActiveRole('partner') ? auth()->user()->partner->id : null;
            $region = Region::query()->find($data['region']);

            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Str::random(8),
                'partner_referral_id' => $partnerId,
            ]);

            Referral::query()->create([
                'user_id' => $user->id,
                'type' => 'venue',
                'referrer_type' => auth()->user()->main_role,
                'referrer_id' => auth()->id(),
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $user->assignRole('venue');

            return $user->venue()->create([
                'name' => $data['name'],
                'primary_contact_name' => $data['primary_contact_name'],
                'contact_phone' => $data['contact_phone'],
                'payout_venue' => $data['payout_venue'],
                'booking_fee' => $data['booking_fee'],
                'contacts' => $data['contacts'],
                'region' => $data['region'],
                'timezone' => $region->timezone,
                'logo_path' => $data['logo_path'],
                'open_days' => [
                    'monday' => 'open',
                    'tuesday' => 'open',
                    'wednesday' => 'open',
                    'thursday' => 'open',
                    'friday' => 'open',
                    'saturday' => 'open',
                    'sunday' => 'open',
                ],
                'daily_prime_bookings_cap' => $data['daily_prime_bookings_cap'] ?? null,
                'daily_non_prime_bookings_cap' => $data['daily_non_prime_bookings_cap'] ?? null,
            ]);
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? self::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        if ($this->getRecord()->logo_path) {
            Storage::disk('do')->setVisibility($this->getRecord()->logo_path, 'public');
        }
    }
}
