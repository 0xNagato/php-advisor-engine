<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Actions\Venue\DeleteVenueAction;
use App\Actions\Venue\GenerateVenueDescriptionWithAI;
use App\Actions\Venue\UpdateVenueGroupEarnings;
use App\Data\VenueMetadata;
use App\Enums\VenueStatus;
use App\Filament\Resources\VenueResource;
use App\Filament\Resources\VenueResource\Components\VenueContactsForm;
use App\Models\Concierge;
use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Partner;
use App\Models\Region;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use App\Services\GooglePlacesService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * Class EditVenue
 *
 * @method Venue getRecord()
 */
class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public function form(Form $form): Form
    {
        $venue = $this->getRecord();

        return $form
            ->schema([
                Section::make('Venue Group Information')
                    ->icon('heroicon-m-building-office-2')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Placeholder::make('venue_group_logo')
                                            ->hiddenLabel()
                                            ->content(function () use ($venue) {
                                                if (! $venue->venueGroup || ! $venue->venueGroup->logo_path) {
                                                    return new HtmlString(
                                                        '<div class="flex justify-center items-center w-24 h-24 bg-gray-100 rounded-lg">
                                                            <svg class="w-10 h-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                        </div>'
                                                    );
                                                }

                                                return new HtmlString(
                                                    '<div class="flex justify-center items-center h-full">
                                                        <img src="'.$venue->venueGroup->logo.'" alt="'.$venue->venueGroup->name.'" class="object-contain w-auto max-w-full h-24" />
                                                    </div>'
                                                );
                                            })
                                            ->visible(fn () => $venue->venue_group_id !== null),
                                    ])
                                    ->columnSpan(['lg' => 1, 'md' => 1, 'sm' => 4])
                                    ->extraAttributes(['class' => 'flex items-center justify-center h-full']),
                                Group::make()
                                    ->schema([
                                        Placeholder::make('venue_group_name')
                                            ->label('Venue Group')
                                            ->content(fn () => $venue->venueGroup?->name ?? 'Not part of a venue group')
                                            ->extraAttributes(['class' => 'text-xl font-bold']),
                                        Placeholder::make('primary_manager')
                                            ->label('Primary Manager')
                                            ->content(fn () => $venue->venueGroup?->primaryManager?->name ?? 'N/A')
                                            ->extraAttributes(['class' => 'text-base'])
                                            ->visible(fn () => $venue->venue_group_id !== null),
                                    ])
                                    ->columnSpan(['lg' => 3, 'md' => 3, 'sm' => 4])
                                    ->extraAttributes(['class' => 'flex flex-col justify-center space-y-1']),
                            ])
                            ->columns([
                                'sm' => 4,
                                'md' => 4,
                                'lg' => 4,
                            ])
                            ->extraAttributes(['class' => 'items-center min-h-[100px]']),
                        ViewField::make('venues_in_group')
                            ->label('Venues')
                            ->view('filament.components.venues-in-group')
                            ->visible(fn () => $venue->venue_group_id !== null),
                        ViewField::make('managers_in_group')
                            ->label('Managers')
                            ->view('filament.components.managers-in-group')
                            ->visible(fn () => $venue->venue_group_id !== null),
                        ViewField::make('concierges_in_group')
                            ->label('Concierges')
                            ->view('filament.components.concierges-in-group')
                            ->visible(fn () => $venue->venue_group_id !== null),
                    ])
                    ->visible(fn () => auth()->user()->hasRole([
                        'super_admin', 'admin',
                    ]) && $venue->venue_group_id !== null),
                Section::make('Venue Information')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
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
                                fn (
                                    Venue $record,
                                    TemporaryUploadedFile $file
                                ): string => $record->slug.'-'.time().'.'.$file->getClientOriginalExtension()
                            ),
                        TextInput::make('name')
                            ->label('Venue Name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->placeholder("123 Main Street\nNew York, NY 10001\nUnited States")
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Enter venue description...')
                            ->columnSpanFull(),
                        Select::make('region')
                            ->placeholder('Select Region')
                            ->options(Region::all()->sortBy('id')->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, Venue $record) {
                                $region = Region::query()->find($state);
                                if ($region) {
                                    $record->timezone = $region->timezone;
                                    $record->save();
                                }
                                $neighborhoods = Neighborhood::query()->where('region', $state)
                                    ->orderBy('name')->pluck('name', 'id');
                                $set('neighborhood', null);
                                $set('neighborhoodOptions', $neighborhoods);

                                $specialities = Specialty::getSpecialtiesByRegion($state);
                                $set('specialty', null);
                                $set('specialtyOptions', $specialities);
                            }),
                        Select::make('neighborhood')
                            ->placeholder('Select Neighborhood')
                            ->options(fn (callable $get) => $get('neighborhoodOptions') ?? [])
                            ->reactive(),
                        Select::make('specialty')
                            ->placeholder('Select Specialty')
                            ->options(fn (callable $get) => $get('specialtyOptions') ?? [])
                            ->multiple()
                            ->reactive(),
                        Select::make('tier')
                            ->placeholder('Select Tier')
                            ->options([
                                1 => 'Gold',
                                2 => 'Silver',
                                null => 'Standard',
                            ])
                            ->default(null)
                            ->helperText('Gold venues appear first in search results, followed by Silver, then Standard. Ordering within tiers is managed in the Tier Ordering tool.'),
                        Placeholder::make('tier_position')
                            ->label('Tier Position')
                            ->content(function () use ($venue) {
                                if (! $venue->region) {
                                    return 'Set region first to see tier position';
                                }

                                // DB-backed position display
                                if (! in_array($venue->tier, [1, 2], true)) {
                                    return new HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">Standard tier</span>');
                                }

                                $list = ReservationService::getVenuesInTier($venue->region, (int) $venue->tier);
                                $position = array_search($venue->id, $list, true);

                                if ($position !== false) {
                                    $labelClass = $venue->tier === 1 ? 'text-yellow-800 bg-yellow-100' : 'text-gray-800 bg-gray-100';
                                    $tierLabel = $venue->tier === 1 ? 'Gold' : 'Silver';

                                    return new HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium '.$labelClass.' rounded-full">Position '.($position + 1).' in '.$tierLabel.'</span>');
                                }

                                $tierLabel = $venue->tier === 1 ? 'Gold' : 'Silver';

                                return new HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">'.$tierLabel.' tier (not ordered)</span>');
                            })
                            ->helperText('This shows the venue\'s position within its tier for the selected region. Use the Tier Ordering tool to change positions.'),
                        FileUpload::make('images')
                            ->label('Images')
                            ->disk('do')
                            ->directory(app()->environment().'/venues/images')
                            ->moveFiles()
                            ->multiple()
                            ->imageEditor()
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(8192)
                            ->imagePreviewHeight('80')
                            ->columnSpanFull()
                            ->getUploadedFileNameForStorageUsing(
                                fn (
                                    Venue $record,
                                    TemporaryUploadedFile $file
                                ): string => $record->slug.'-'.time().'-'.uniqid().'.'.$file->getClientOriginalExtension()
                            )
                            ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file): ?array {
                                // Handle both raw paths and full URLs
                                $url = $file;
                                if (! str_contains($file, 'prima-bucket.nyc3.digitaloceanspaces.com')) {
                                    $url = Storage::disk('do')->url($file);
                                }

                                return [
                                    'name' => basename($file),
                                    'size' => 0,
                                    'type' => null,
                                    'url' => $url,
                                ];
                            })
                            ->deleteUploadedFileUsing(static function (BaseFileUpload $component, string $file): void {
                                $disk = Storage::disk('do');

                                $path = parse_url($file, PHP_URL_PATH) ?: $file;
                                $path = ltrim($path, '/');

                                if ($disk->exists($path)) {
                                    try {
                                        $disk->delete($path);
                                    } catch (Throwable $e) {
                                        logger()->warning('Failed to delete image', [
                                            'path' => $path,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }),
                        TextInput::make('primary_contact_name')
                            ->label('Primary Contact Name')
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Primary Contact Phone')
                            ->onlyCountries(config('app.countries'))
                            ->displayNumberFormat(PhoneInputNumberType::E164)
                            ->disallowDropdown()
                            ->validateFor(
                                country: config('app.countries'),
                                lenient: true,
                            )
                            ->required()
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
                            ->nullable()
                            ->dehydrateStateUsing(function ($state) {
                                if (blank($state)) {
                                    return null;
                                }

                                return now()->setTimeFromTimeString($state);
                            })
                            ->formatStateUsing(function ($state) {
                                if (blank($state)) {
                                    return null;
                                }

                                return $state instanceof Carbon ? $state->format('H:i') : date('H:i',
                                    strtotime($state));
                            }),
                        Select::make('advance_booking_window')
                            ->label('Advance Booking Window')
                            ->options(array_combine(range(0, 7), range(0, 7))) // Generates options from 0 to 7
                            ->default(0)
                            ->helperText('Set the number of days in advance that reservations can be made. Use 0 for no restrictions.'),
                        Toggle::make('no_wait')
                            ->label('No Wait')
                            ->helperText('When enabled, guests will be seated immediately upon arrival')
                            ->default(false),
                        Toggle::make('is_omakase')
                            ->label('Is Omakase')
                            ->live()
                            ->helperText('Enable if guests pay for the meal rather than just the reservation. This affects how payouts are calculated.')
                            ->default(false),
                        Textarea::make('omakase_details')
                            ->label('Omakase Details')
                            ->visible(fn (Get $get): bool => $get('is_omakase'))
                            ->nullable(),
                        TextInput::make('omakase_concierge_fee')
                            ->label('Omakase Concierge Fee Per Guest')
                            ->prefix(fn (Get $get) => Region::getCurrencySymbolForRegion($get('region') ?? 'miami'))
                            ->numeric()
                            ->visible(fn (Get $get): bool => $get('is_omakase'))
                            ->helperText('Amount paid to concierge per guest for each Omakase booking')
                            ->required(fn (Get $get): bool => $get('is_omakase'))
                            ->dehydrateStateUsing(fn ($state) => $state * 100)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : null),
                    ]),
                Section::make('Cuisine Information')
                    ->icon('phosphor-bowl-steam-bold')
                    ->schema([
                        CheckboxList::make('cuisines')
                            ->hiddenLabel()
                            ->options(Cuisine::query()->pluck('name', 'id'))
                            ->descriptions(Cuisine::query()->pluck('description', 'id'))
                            ->searchable()->live()
                            ->gridDirection('row')
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                Repeater::make('contacts')
                    ->columnSpanFull()
                    ->addActionLabel('Add Contact')
                    ->label('Contacts')
                    ->schema(
                        VenueContactsForm::schema()
                    ),

                Section::make('Payout Information')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('booking_fee')
                            ->label('Booking Fee')
                            ->prefix(fn (Get $get) => Region::getCurrencySymbolForRegion($get('region') ?? 'miami'))
                            ->default(200)
                            ->numeric()
                            ->required(),
                        TextInput::make('increment_fee')
                            ->label('Increment Fee')
                            ->prefix(fn (Get $get) => Region::getCurrencySymbolForRegion($get('region') ?? 'miami'))
                            ->default(0)
                            ->numeric(),
                        TextInput::make('payout_venue')
                            ->label('Payout Venue')
                            ->default(60)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ]),
                Section::make('Special Pricing')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Repeater::make('special_pricing')
                            ->hiddenLabel()
                            ->relationship('specialPricing')
                            ->addActionLabel('Add Day')
                            ->label('Special Pricing')
                            ->schema([
                                TextInput::make('date')
                                    ->label('Date')
                                    ->type('date')
                                    ->required(),
                                TextInput::make('fee')
                                    ->label('Fee')
                                    ->prefix(fn () => Region::getCurrencySymbolForRegion($this->getRecord()->region ?? 'miami'))
                                    ->numeric()
                                    ->required(),
                            ]),
                    ]),
                Section::make('Tax / VAT')
                    ->icon('heroicon-m-receipt-percent')
                    ->schema([
                        TextInput::make('vat')
                            ->label('VAT Number')
                            ->maxLength(100)
                            ->nullable(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $currentPartnerName = $this->getRecord()->partnerReferral ? $this->getRecord()->partnerReferral->user->name : 'No Partner';
        $venue = $this->getRecord();

        return [
            ActionGroup::make([
                Action::make('convertToVenueGroup')
                    ->label('Convert to Venue Group')
                    ->icon('heroicon-o-building-office-2')
                    ->color('success')
                    ->visible(fn () => $venue->venue_group_id === null)
                    ->form([
                        TextInput::make('group_name')
                            ->label('Venue Group Name')
                            ->default(fn () => $venue->name.' Group')
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Venue Group Logo')
                            ->disk('do')
                            ->directory(app()->environment().'/venue-groups')
                            ->moveFiles()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(8192)
                            ->imagePreviewHeight('254')
                            ->getUploadedFileNameForStorageUsing(
                                fn (
                                    $record,
                                    TemporaryUploadedFile $file
                                ): string => str($venue->name.' Group')->slug().'-'.time().'.'.$file->getClientOriginalExtension()
                            ),
                        Select::make('additional_venues')
                            ->label('Additional Venues')
                            ->multiple()
                            ->options(
                                Venue::query()
                                    ->where('id', '!=', $venue->id)
                                    ->where('venue_group_id', null)
                                    ->pluck('name', 'id')
                            )
                            ->helperText('Select other venues to add to this venue group (optional)'),
                    ])
                    ->action(function (array $data) use ($venue) {
                        try {
                            DB::transaction(function () use ($data, $venue) {
                                // Create the venue group
                                $venueGroup = new VenueGroup;
                                $venueGroup->name = $data['group_name'];
                                $venueGroup->primary_manager_id = $venue->user_id;
                                $venueGroup->logo_path = $data['logo_path'] ?? null;
                                $venueGroup->save();

                                // Make the logo public if it exists
                                if (filled($data['logo_path'])) {
                                    Storage::disk('do')->setVisibility($data['logo_path'], 'public');
                                }

                                // Add the current venue to the venue group
                                $venue->venue_group_id = $venueGroup->id;
                                $venue->save();

                                /** @var User $user */
                                $user = $venue->user;
                                $user->removeRole('venue');
                                $user->assignRole('venue_manager');

                                // Add the user as a manager in the venue group
                                $venueGroup->managers()->attach($user->id, [
                                    'current_venue_id' => $venue->id,
                                    'allowed_venue_ids' => json_encode([$venue->id]),
                                    'is_current' => true,
                                ]);

                                // Add additional venues if selected
                                if (filled($data['additional_venues'])) {
                                    $allowedVenueIds = [$venue->id];
                                    $addedVenues = [$venue]; // Include the original venue

                                    foreach ($data['additional_venues'] as $additionalVenueId) {
                                        $additionalVenue = Venue::query()->find($additionalVenueId);
                                        if ($additionalVenue) {
                                            // Update the venue's user_id to match the original venue's user_id
                                            $additionalVenue->user_id = $venue->user_id;
                                            $additionalVenue->venue_group_id = $venueGroup->id;
                                            $additionalVenue->save();
                                            $allowedVenueIds[] = $additionalVenue->id;
                                            $addedVenues[] = $additionalVenue;
                                        }
                                    }

                                    // Update allowed venues for the manager
                                    $allVenueIds = $venueGroup->venues()->pluck('id')->toArray();
                                    $venueGroup->managers()->updateExistingPivot($user->id, [
                                        'allowed_venue_ids' => json_encode($allVenueIds),
                                    ]);

                                    // Update earnings for all venues in the group to assign them to the primary manager
                                    UpdateVenueGroupEarnings::run($venueGroup, $addedVenues);
                                } else {
                                    // Just update earnings for the original venue
                                    UpdateVenueGroupEarnings::run($venueGroup, [$venue]);
                                }
                            });

                            Notification::make()
                                ->title('Venue Group Created')
                                ->body('The venue has been successfully converted to a venue group.')
                                ->success()
                                ->send();

                            // Redirect to refresh the page
                            $this->redirect(VenueResource::getUrl('edit', ['record' => $venue->id]));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Failed to create venue group: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Convert to Venue Group')
                    ->modalDescription('This will convert the venue into a venue group and make the venue owner a venue manager. Any additional venues will be linked to the same user account.'),

                Action::make('manageVenueGroup')
                    ->label('Manage Venue Group')
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary')
                    ->visible(fn () => $venue->venue_group_id !== null)
                    ->form([
                        TextInput::make('group_name')
                            ->label('Venue Group Name')
                            ->default(fn () => $venue->venueGroup->name)
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Venue Group Logo')
                            ->disk('do')
                            ->directory(app()->environment().'/venue-groups')
                            ->moveFiles()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(8192)
                            ->imagePreviewHeight('254')
                            ->default(fn () => $venue->venueGroup->logo_path)
                            ->getUploadedFileNameForStorageUsing(
                                fn (
                                    $record,
                                    TemporaryUploadedFile $file
                                ): string => $venue->venueGroup->slug.'-'.time().'.'.$file->getClientOriginalExtension()
                            ),
                        Select::make('additional_venues')
                            ->label('Add Venues to Group')
                            ->multiple()
                            ->options(
                                fn () => Venue::query()
                                    ->where('venue_group_id', null)
                                    ->pluck('name', 'id')
                            )
                            ->helperText('Select venues to add to this venue group'),
                    ])
                    ->action(function (array $data) use ($venue) {
                        try {
                            DB::transaction(function () use ($data, $venue) {
                                $venueGroup = $venue->venueGroup;

                                // Update venue group name and logo
                                $venueGroup->update([
                                    'name' => $data['group_name'],
                                    'logo_path' => $data['logo_path'],
                                ]);

                                // Make the logo public
                                if ($data['logo_path']) {
                                    Storage::disk('do')->setVisibility($data['logo_path'], 'public');
                                }

                                // Add additional venues if selected
                                if (filled($data['additional_venues'])) {
                                    $allVenueIds = $venueGroup->venues()->pluck('id')->toArray();
                                    $primaryManager = $venueGroup->primaryManager;
                                    $addedVenues = [];

                                    foreach ($data['additional_venues'] as $additionalVenueId) {
                                        $additionalVenue = Venue::query()->find($additionalVenueId);
                                        if ($additionalVenue) {
                                            // Update the venue's user_id to match the venue group's primary manager
                                            $additionalVenue->user_id = $primaryManager->id;
                                            $additionalVenue->venue_group_id = $venueGroup->id;
                                            $additionalVenue->save();
                                            $allVenueIds[] = $additionalVenue->id;

                                            // Store the venue for earnings update
                                            $addedVenues[] = $additionalVenue;
                                        }
                                    }

                                    // Update allowed venues for all managers
                                    foreach ($venueGroup->managers as $manager) {
                                        $venueGroup->managers()->updateExistingPivot($manager->id, [
                                            'allowed_venue_ids' => json_encode($allVenueIds),
                                        ]);
                                    }

                                    // Update earnings for added venues
                                    if (filled($addedVenues)) {
                                        $earningsUpdated = UpdateVenueGroupEarnings::run(
                                            $venueGroup,
                                            $addedVenues
                                        );
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Venue Group Updated')
                                ->body('The venue group name, logo, and venues have been successfully updated.')
                                ->success()
                                ->send();

                            // Redirect to refresh the page
                            $this->redirect(VenueResource::getUrl('edit', ['record' => $venue->id]));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Failed to update venue group: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Manage Venue Group')
                    ->modalDescription('Update the venue group name, logo, and add additional venues. Any new venues will be linked to the primary manager\'s user account.'),

                Action::make('mergeVenueGroups')
                    ->label('Merge Venue Groups')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('danger')
                    ->visible(fn () => $venue->venue_group_id !== null && auth()->user()->hasRole('super_admin'))
                    ->form([
                        Select::make('target_venue_group_id')
                            ->label('Venue Group to Merge')
                            ->helperText('Select the venue group that will be merged into the current venue group. All venues and managers from the selected group will be transferred to the current group.')
                            ->options(fn () => VenueGroup::query()
                                ->where('id', '!=', $venue->venue_group_id)
                                ->pluck('name', 'id'))
                            ->required(),
                        Toggle::make('transfer_primary_manager')
                            ->label('Transfer Primary Manager')
                            ->helperText('If enabled, the primary manager from the selected venue group will become the primary manager of the current venue group.')
                            ->default(false),
                    ])
                    ->action(function (array $data) use ($venue) {
                        try {
                            DB::transaction(function () use ($data, $venue) {
                                $currentVenueGroup = $venue->venueGroup;
                                $targetVenueGroup = VenueGroup::query()->findOrFail($data['target_venue_group_id']);

                                // Get all venues from the target venue group
                                $venues = $targetVenueGroup->venues;

                                // Get all managers from the target venue group
                                $managers = $targetVenueGroup->managers;

                                // Update primary manager if requested
                                if ($data['transfer_primary_manager'] && $targetVenueGroup->primary_manager_id) {
                                    $currentVenueGroup->update([
                                        'primary_manager_id' => $targetVenueGroup->primary_manager_id,
                                    ]);
                                }

                                // Transfer all venues to the current venue group
                                foreach ($venues as $venueToTransfer) {
                                    $venueToTransfer->update([
                                        'venue_group_id' => $currentVenueGroup->id,
                                    ]);
                                }

                                // Transfer all managers to the current venue group
                                foreach ($managers as $manager) {
                                    // Check if manager already exists in current group
                                    $existingManager = $currentVenueGroup->managers()->where(
                                        'user_id',
                                        $manager->id
                                    )->first();

                                    if ($existingManager) {
                                        // Manager already exists in current group, merge allowed venues
                                        $currentAllowedVenueIds = json_decode(
                                            $existingManager->pivot->allowed_venue_ids ?? '[]',
                                            true
                                        );
                                        $newAllowedVenueIds = json_decode(
                                            $manager->pivot->allowed_venue_ids ?? '[]',
                                            true
                                        );
                                        $mergedAllowedVenueIds = array_unique(array_merge(
                                            $currentAllowedVenueIds,
                                            $newAllowedVenueIds
                                        ));

                                        $currentVenueGroup->managers()->updateExistingPivot($manager->id, [
                                            'allowed_venue_ids' => json_encode($mergedAllowedVenueIds),
                                        ]);
                                    } else {
                                        // Manager doesn't exist in current group, add them
                                        $currentVenueGroup->managers()->attach($manager->id, [
                                            'current_venue_id' => $manager->pivot->current_venue_id,
                                            'allowed_venue_ids' => $manager->pivot->allowed_venue_ids,
                                            'is_current' => $manager->pivot->is_current,
                                        ]);
                                    }
                                }

                                // Get all venue IDs from the combined group
                                $allVenueIds = $currentVenueGroup->venues()->pluck('id')->toArray();

                                // Ensure all managers from both groups have access to all venues
                                foreach ($currentVenueGroup->managers as $manager) {
                                    $currentVenueGroup->managers()->updateExistingPivot(
                                        $manager->id,
                                        ['allowed_venue_ids' => json_encode($allVenueIds)]
                                    );
                                }

                                // Update concierges to point to the current venue group
                                Concierge::query()->where('venue_group_id', $targetVenueGroup->id)
                                    ->update(['venue_group_id' => $currentVenueGroup->id]);

                                // Delete the target venue group
                                $targetVenueGroup->delete();

                                // Update earnings for transferred venues
                                $earningsUpdated = UpdateVenueGroupEarnings::run(
                                    $currentVenueGroup,
                                    $venues
                                );
                            });

                            Notification::make()
                                ->title('Venue Groups Merged')
                                ->body('The selected venue group has been successfully merged into the current venue group.')
                                ->success()
                                ->send();

                            // Redirect to refresh the page
                            $this->redirect(VenueResource::getUrl('edit', ['record' => $venue->id]));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Failed to merge venue groups: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Merge Venue Groups')
                    ->modalDescription('This will merge the selected venue group into the current venue group. All venues and managers from the selected group will be transferred to the current venue group, and the selected venue group will be deleted. This action cannot be undone.'),

                Action::make('setPrimaryManager')
                    ->label('Set Primary Manager')
                    ->icon('heroicon-o-user')
                    ->color('warning')
                    ->visible(fn () => $venue->venue_group_id !== null && auth()->user()->hasRole([
                        'super_admin', 'admin',
                    ]))
                    ->form([
                        Select::make('primary_manager_id')
                            ->label('Primary Manager')
                            ->options(fn () => $venue->venueGroup->managers->pluck('name', 'id'))
                            ->default(fn () => $venue->venueGroup->primary_manager_id)
                            ->required(),
                    ])
                    ->action(function (array $data) use ($venue) {
                        try {
                            DB::transaction(function () use ($data, $venue) {
                                $venueGroup = $venue->venueGroup;

                                // Update the primary manager
                                $venueGroup->update([
                                    'primary_manager_id' => $data['primary_manager_id'],
                                ]);

                                // Update all venues in the group to have the same user_id
                                $venueGroup->venues()->update([
                                    'user_id' => $data['primary_manager_id'],
                                ]);
                            });

                            Notification::make()
                                ->title('Primary Manager Updated')
                                ->body('The primary manager has been successfully updated and all venues have been assigned to this user.')
                                ->success()
                                ->send();

                            // Redirect to refresh the page
                            $this->redirect(VenueResource::getUrl('edit', ['record' => $venue->id]));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Failed to update primary manager: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Set Primary Manager')
                    ->modalDescription('Choose the primary manager for this venue group. This user will become the owner of all venues in the group.'),

                Action::make('changePartner')
                    ->label('Change Partner ('.$currentPartnerName.')')
                    ->icon('heroicon-o-briefcase')
                    ->form([
                        Select::make('new_partner_id')
                            ->label('New Partner')
                            ->options(Partner::with('user')->get()->pluck('user.name', 'id'))
                            ->default($this->getRecord()->user->partner_referral_id)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $this->getRecord()->updateReferringPartner($data['new_partner_id']);
                            Notification::make()
                                ->title('Partner Changed Successfully')
                                ->success()
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to change the partner for this venue?'),

                Action::make('changeStatus')
                    ->label(fn () => 'Set Status ('.$this->getRecord()->status->getLabel().')')
                    ->icon(fn () => match ($this->getRecord()->status->value) {
                        VenueStatus::DRAFT->value => 'heroicon-o-document',
                        VenueStatus::PENDING->value => 'heroicon-o-clock',
                        VenueStatus::ACTIVE->value => 'heroicon-o-check-circle',
                        VenueStatus::SUSPENDED->value => 'heroicon-o-x-circle',
                        VenueStatus::HIDDEN->value => 'heroicon-o-eye-slash',
                        VenueStatus::UPCOMING->value => 'heroicon-o-calendar',
                    })
                    ->color(fn () => match ($this->getRecord()->status->value) {
                        VenueStatus::DRAFT->value => 'gray',
                        VenueStatus::PENDING->value => 'warning',
                        VenueStatus::ACTIVE->value => 'success',
                        VenueStatus::SUSPENDED->value => 'danger',
                        VenueStatus::HIDDEN->value => 'gray',
                        VenueStatus::UPCOMING->value => 'info',
                    })
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->options(VenueStatus::class)
                            ->default(fn () => $this->getRecord()->status->value)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $venue = $this->getRecord();
                        $newStatus = VenueStatus::from($data['status']);

                        $venue->update([
                            'status' => $newStatus,
                        ]);

                        Notification::make()
                            ->title('Status Updated')
                            ->body('Venue status has been set to '.$newStatus->getLabel())
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Change Venue Status')
                    ->modalDescription('Are you sure you want to change the status of this venue?'),

                Action::make('syncGoogleData')
                    ->label('Sync Google Data')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Section::make('Current Data')
                            ->schema([
                                Placeholder::make('current_rating')
                                    ->label('Current Rating')
                                    ->content(fn () => $venue->metadata?->rating ?
                                        $venue->metadata->rating.'/5 ('.$venue->metadata->reviewCount.' reviews)' :
                                        'No rating data'),
                                Placeholder::make('current_description')
                                    ->label('Current Description')
                                    ->content(fn () => $venue->description ?: 'No description'),
                                Placeholder::make('last_synced')
                                    ->label('Last Synced')
                                    ->content(fn () => $venue->metadata?->lastSyncedAt ?
                                        Carbon::parse($venue->metadata->lastSyncedAt)->diffForHumans() :
                                        'Never synced'),
                            ])
                            ->collapsible(),
                        Section::make('Sync Options')
                            ->schema([
                                Toggle::make('skip_photos')
                                    ->label('Skip Photos')
                                    ->helperText('Skip downloading and uploading photos')
                                    ->default(false),
                                Toggle::make('overwrite_description')
                                    ->label('Overwrite Description')
                                    ->helperText('Overwrite existing descriptions with Google data')
                                    ->default(false),
                                Toggle::make('ratings_only')
                                    ->label('Ratings Only')
                                    ->helperText('Only update ratings, skip everything else')
                                    ->default(false),
                            ]),
                    ])
                    ->action(function (array $data, GooglePlacesService $googlePlaces) use ($venue) {
                        try {
                            // Build search query
                            $searchQuery = $venue->name;
                            if ($venue->address) {
                                $searchQuery .= ' '.$venue->address;
                            }

                            // Search for the venue
                            $placeData = $googlePlaces->searchPlace($searchQuery, $venue->region);

                            if (! $placeData) {
                                Notification::make()
                                    ->title('No Results')
                                    ->body('No Google Places results found for this venue.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            // Get detailed place data if needed
                            if ($placeData->placeId && ! $placeData->rating) {
                                $detailedData = $googlePlaces->getPlaceDetails($placeData->placeId);
                                if ($detailedData) {
                                    $placeData = $detailedData;
                                }
                            }

                            if ($data['ratings_only']) {
                                // Only update rating in metadata
                                $metadata = $venue->metadata ?? new VenueMetadata;
                                if ($placeData->rating !== null) {
                                    $metadata->rating = $placeData->rating;
                                    $metadata->reviewCount = $placeData->userRatingsTotal;
                                    $metadata->lastSyncedAt = now()->toISOString();
                                    $venue->metadata = $metadata;
                                }
                            } else {
                                // Update address if missing
                                if (blank($venue->address) && $placeData->formattedAddress) {
                                    $venue->address = $placeData->formattedAddress;
                                }

                                // Update description based on flags
                                if ($placeData->editorialSummary || $placeData->generativeSummary) {
                                    if ($data['overwrite_description'] || blank($venue->description)) {
                                        $venue->description = $placeData->editorialSummary ?? $placeData->generativeSummary;
                                    }
                                }

                                // Update metadata
                                $venue->updateMetadataFromGoogle($placeData, $data['skip_photos']);
                            }

                            $venue->save();

                            Notification::make()
                                ->title('Google Data Synced')
                                ->body('Successfully synced venue data from Google Places.')
                                ->success()
                                ->send();

                            // Redirect to refresh the page
                            $this->redirect(VenueResource::getUrl('edit', ['record' => $venue->id]));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body('Failed to sync Google data: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Sync Google Data')
                    ->modalDescription('This will fetch the latest information from Google Places API and update the venue.'),

                Action::make('generateAIDescription')
                    ->label('Generate AI Description')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->form([
                        Section::make('Current Description')
                            ->schema([
                                Placeholder::make('current_description')
                                    ->hiddenLabel()
                                    ->content(function () use ($venue) {
                                        if (blank($venue->description)) {
                                            return 'No description currently set.';
                                        }

                                        return new HtmlString(
                                            '<div class="prose prose-sm max-w-none">'.
                                            nl2br(e($venue->description)).
                                            '</div>'
                                        );
                                    }),
                            ])
                            ->visible(fn () => ! blank($venue->description)),
                        Section::make('Venue Information')
                            ->schema([
                                Placeholder::make('venue_info')
                                    ->hiddenLabel()
                                    ->content(function () use ($venue) {
                                        $info = [];
                                        if ($venue->metadata?->rating) {
                                            $info[] = '⭐ '.$venue->metadata->rating.'/5 ('.$venue->metadata->reviewCount.' reviews)';
                                        }
                                        if ($venue->cuisines) {
                                            $cuisineNames = Cuisine::query()->whereIn('id', $venue->cuisines)->pluck('name')->join(', ');
                                            $info[] = '🍽️ '.$cuisineNames;
                                        }
                                        if ($venue->specialty) {
                                            $info[] = '✨ '.implode(', ', $venue->specialty);
                                        }
                                        if ($venue->metadata?->priceLevel) {
                                            $info[] = '💰 '.str_repeat('$', $venue->metadata->priceLevel);
                                        }

                                        return new HtmlString(implode('<br>', $info) ?: 'Limited information available for AI generation.');
                                    }),
                            ])
                            ->collapsible(),
                        Section::make('Generation Options')
                            ->schema([
                                Select::make('provider')
                                    ->label('AI Provider')
                                    ->options([
                                        'anthropic' => 'Claude (Anthropic)',
                                        'openai' => 'OpenAI GPT-4',
                                    ])
                                    ->default('anthropic')
                                    ->required(),
                                Toggle::make('overwrite')
                                    ->label('Overwrite Existing')
                                    ->helperText('Replace the current description even if one exists')
                                    ->default(false)
                                    ->visible(fn () => ! blank($venue->description)),
                            ]),
                    ])
                    ->action(function (array $data, GenerateVenueDescriptionWithAI $generator) use ($venue) {
                        try {
                            // Check if we should proceed
                            if (! $data['overwrite'] && ! blank($venue->description)) {
                                Notification::make()
                                    ->title('Description Exists')
                                    ->body('This venue already has a description. Enable "Overwrite Existing" to replace it.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $description = $generator->handle($venue, $data['provider']);

                            if ($description) {
                                $venue->description = $description;
                                $venue->save();

                                Notification::make()
                                    ->title('Description Generated')
                                    ->body('AI-generated description has been saved successfully.')
                                    ->success()
                                    ->send();

                                // Redirect to refresh the page
                                $this->redirect(VenueResource::getUrl('edit', ['record' => $venue->id]));
                            } else {
                                Notification::make()
                                    ->title('Generation Failed')
                                    ->body('Failed to generate description. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Failed to generate description: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Generate AI Description')
                    ->modalDescription('Generate a professional description for this venue using AI based on its metadata and features.'),

                Action::make('downloadLogo')
                    ->label('Download Logo')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn () => $venue->logo_path !== null)
                    ->action(function () use ($venue) {
                        try {
                            if (! $venue->logo_path) {
                                Notification::make()
                                    ->title('No Logo')
                                    ->body('This venue does not have a logo to download.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            if (! Storage::disk('do')->exists($venue->logo_path)) {
                                Notification::make()
                                    ->title('File Not Found')
                                    ->body('The logo file could not be found.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Get file extension from the stored path
                            $extension = pathinfo($venue->logo_path, PATHINFO_EXTENSION);

                            // Create a clean filename
                            $filename = str($venue->name)->slug().'-logo.'.$extension;

                            return Storage::disk('do')->download($venue->logo_path, $filename);
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Download Failed')
                                ->body('Failed to download logo: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('delete')
                    ->label('Delete Venue')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn () => in_array(auth()->id(), config('app.god_ids', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Venue')
                    ->modalDescription('Are you sure you want to delete this venue? This action cannot be undone and all venue data will be permanently removed.')
                    ->action(function () use ($venue) {
                        try {
                            // Check if this is the only venue in a group before deleting
                            $isOnlyVenueInGroup = $venue->venue_group_id !== null &&
                                $venue->venueGroup &&
                                $venue->venueGroup->venues()->count() === 1;

                            DeleteVenueAction::run($venue);

                            if ($isOnlyVenueInGroup) {
                                Notification::make()
                                    ->title('Venue and Venue Group Deleted')
                                    ->body('The venue and its venue group have been successfully deleted. Any managers or concierges who were only associated with this venue group have been suspended.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Venue Deleted')
                                    ->body('The venue has been successfully deleted.')
                                    ->success()
                                    ->send();
                            }

                            $this->redirect(VenueResource::getUrl('index'));
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('primary')
                ->size(ActionSize::Small)
                ->iconButton(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['region'])) {
            $data['neighborhoodOptions'] = Neighborhood::query()->where('region', $data['region'])
                ->orderBy('name')->pluck('name', 'id');
            $data['specialtyOptions'] = Specialty::getSpecialtiesByRegion($data['region']);
        }

        // Get raw images data from the database instead of the mutated accessor
        $venue = $this->getRecord();
        $rawImages = $venue->getRawOriginal('images');

        if ($rawImages !== null) {
            $images = is_string($rawImages) ? json_decode($rawImages, true) : $rawImages;
            $data['images'] = is_array($images) ? $images : [];
        }

        if (! isset($data['contacts'])) {
            return $data;
        }

        $data['contacts'] = collect($data['contacts'])
            ->filter(fn ($contact) => $contact['contact_name'] !== 'Additional Contact')
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $venue = $this->getRecord();

        // Make logo public
        if ($venue->logo_path) {
            Storage::disk('do')->setVisibility($venue->logo_path, 'public');
        }

        // Make all images public
        if ($venue->images && is_array($venue->images)) {
            foreach ($venue->images as $imagePath) {
                if ($imagePath && Storage::disk('do')->exists($imagePath)) {
                    Storage::disk('do')->setVisibility($imagePath, 'public');
                }
            }
        }
    }
}
