<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Enums\VenueStatus;
use App\Filament\Resources\VenueResource;
use App\Filament\Resources\VenueResource\Components\VenueContactsForm;
use App\Models\Concierge;
use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Partner;
use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
                                                        '<div class="flex items-center justify-center w-24 h-24 bg-gray-100 rounded-lg">
                                                            <svg class="w-10 h-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                        </div>'
                                                    );
                                                }

                                                return new HtmlString(
                                                    '<div class="flex items-center justify-center h-full">
                                                        <img src="'.$venue->venueGroup->logo.'" alt="'.$venue->venueGroup->name.'" class="object-contain w-auto h-24 max-w-full" />
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
                            }),
                        Select::make('neighborhood')
                            ->placeholder('Select Neighborhood')
                            ->options(fn (callable $get) => $get('neighborhoodOptions') ?? [])
                            ->reactive(),
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

                                return $state instanceof Carbon ? $state->format('H:i') : date('H:i', strtotime($state));
                            }),
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
                            ->prefix('$')
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
                            ->prefix('$')
                            ->default(200)
                            ->numeric()
                            ->required(),
                        TextInput::make('increment_fee')
                            ->label('Increment Fee')
                            ->prefix('$')
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
                                    ->prefix('$')
                                    ->numeric()
                                    ->required(),
                            ]),
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
                                    \App\Actions\Venue\UpdateVenueGroupEarnings::run($venueGroup, $addedVenues);
                                } else {
                                    // Just update earnings for the original venue
                                    \App\Actions\Venue\UpdateVenueGroupEarnings::run($venueGroup, [$venue]);
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
                                    if (! empty($addedVenues)) {
                                        $earningsUpdated = \App\Actions\Venue\UpdateVenueGroupEarnings::run($venueGroup, $addedVenues);
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
                                    $existingManager = $currentVenueGroup->managers()->where('user_id',
                                        $manager->id)->first();

                                    if ($existingManager) {
                                        // Manager already exists in current group, merge allowed venues
                                        $currentAllowedVenueIds = json_decode($existingManager->pivot->allowed_venue_ids ?? '[]',
                                            true);
                                        $newAllowedVenueIds = json_decode($manager->pivot->allowed_venue_ids ?? '[]',
                                            true);
                                        $mergedAllowedVenueIds = array_unique(array_merge($currentAllowedVenueIds,
                                            $newAllowedVenueIds));

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
                                $earningsUpdated = \App\Actions\Venue\UpdateVenueGroupEarnings::run($currentVenueGroup, $venues);
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
        if ($this->getRecord()->logo_path) {
            Storage::disk('do')->setVisibility($this->getRecord()->logo_path, 'public');
        }
    }
}
