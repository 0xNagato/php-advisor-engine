<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Constants\BookingPercentages;
use App\Enums\VenueStatus;
use App\Enums\VipCodeTemplate;
use App\Filament\Resources\ConciergeResource;
use App\Models\Region;
use App\Models\Venue;
use App\Traits\ManagesVenueCollections;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Log;

class EditConcierge extends EditRecord
{
    use ManagesVenueCollections;

    protected static string $resource = ConciergeResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Hotel Information')
                    ->icon('heroicon-m-building-office')
                    ->schema([
                        TextInput::make('hotel_name')
                            ->label('Hotel Name')
                            ->placeholder('Hotel Name')
                            ->required(),
                    ]),
                Section::make('QR Concierge Configuration')
                    ->icon('heroicon-m-qr-code')
                    ->description('Configure QR Concierge settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_qr_concierge')
                                    ->label('QR Concierge')
                                    ->helperText('Enable this to designate this concierge as a QR concierge with QR code capabilities')
                                    ->reactive(),
                                TextInput::make('revenue_percentage')
                                    ->label('Revenue Percentage')
                                    ->helperText('Percentage of revenue this QR concierge will receive (default: 50%)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(BookingPercentages::VIP_ACCESS_DEFAULT_PERCENTAGE)
                                    ->suffix('%')
                                    ->visible(fn (Get $get): bool => $get('is_qr_concierge'))
                                    ->required(fn (Get $get): bool => $get('is_qr_concierge')),
                            ]),
                    ])
                    ->collapsible(),
                Section::make('Override')
                    ->icon('heroicon-o-lock-open')
                    ->schema([
                        Toggle::make('can_override_duplicate_checks')
                            ->label('Can Override Duplicate Bookings')
                            ->helperText('Allow this concierge to bypass duplicate booking restrictions')
                            ->columnSpanFull(),
                    ]),
                Section::make('Feature Permissions')
                    ->icon('heroicon-m-user-group')
                    ->description('Configure concierge-level features and VIP management permissions')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('can_manage_own_branding')
                                    ->label('Enable Branding Management')
                                    ->helperText('Enable concierge-level branding management features')
                                    ->reactive(),
                                Toggle::make('can_manage_own_collections')
                                    ->label('Enable Venue Collections')
                                    ->helperText('Enable concierge-level venue collection features')
                                    ->reactive(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('can_manage_vip_branding')
                                    ->label('Enable VIP Branding Management')
                                    ->helperText('Enable VIP code-level branding management features'),
                                Toggle::make('can_manage_vip_collections')
                                    ->label('Enable VIP Collections Management')
                                    ->helperText('Enable VIP code-level venue collection features'),
                            ]),
                    ])
                    ->collapsible(),
                Section::make('Affiliate Branding Configuration')
                    ->icon('heroicon-m-paint-brush')
                    ->description('Configure branding for white-labeled booking experience')
                    ->visible(fn (Get $get): bool => $get('can_manage_own_branding') === true)
                    ->schema([
                        // Brand Information
                        Grid::make(2)
                            ->schema([
                                TextInput::make('branding.brand_name')
                                    ->label('Brand Name')
                                    ->helperText('The name of your brand for the booking experience')
                                    ->placeholder('Your Brand Name')
                                    ->required(),
                                TextInput::make('branding.redirect_url')
                                    ->label('Redirect URL')
                                    ->helperText('Optional URL to redirect users after booking completion')
                                    ->url()
                                    ->placeholder('https://example.com/thank-you'),
                            ]),
                        Select::make('branding.template')
                            ->label('Default Booking Template')
                            ->options(VipCodeTemplate::options())
                            ->default(VipCodeTemplate::AVAILABILITY_CALENDAR->value)
                            ->helperText('Default booking template for this concierge (can be overridden per VIP code)')
                            ->columnSpan(1),
                        Textarea::make('branding.description')
                            ->label('Description')
                            ->helperText('Description for the booking experience')
                            ->placeholder('Welcome to our exclusive booking experience...')
                            ->rows(3)
                            ->columnSpanFull(),

                        // Visual Branding
                        Section::make('Visual Branding')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('branding.logo_url')
                                    ->label('Logo')
                                    ->helperText('Upload a logo for the white-labeled booking experience')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('do')
                                    ->directory(app()->environment().'/concierges/logos')
                                    ->moveFiles()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(8192)
                                    ->imagePreviewHeight('254')
                                    ->getUploadedFileNameForStorageUsing(
                                        function (TemporaryUploadedFile $file, Get $get) {
                                            $concierge = $this->getRecord();
                                            $hotelName = $concierge ? Str::slug($concierge->hotel_name) : 'concierge';

                                            return $hotelName.'-logo-'.time().'.'.$file->getClientOriginalExtension();
                                        }
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),

                        // Influencer Information
                        Section::make('Influencer Information')
                            ->icon('heroicon-m-user-circle')
                            ->description('Optional influencer/curator details for personalized experience')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('branding.influencer_name')
                                            ->label('Influencer Name')
                                            ->helperText('Name of the influencer or curator')
                                            ->placeholder('John Doe'),
                                        TextInput::make('branding.influencer_handle')
                                            ->label('Social Handle')
                                            ->helperText('Social media handle (without @)')
                                            ->placeholder('johndoe'),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('branding.follower_count')
                                            ->label('Follower Count')
                                            ->helperText('Number of followers (e.g., 12.5K, 1M)')
                                            ->placeholder('12.5K'),
                                        TextInput::make('branding.social_url')
                                            ->label('Social Media URL')
                                            ->helperText('Link to social media profile')
                                            ->url()
                                            ->placeholder('https://instagram.com/johndoe'),
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(),

                        // Color Scheme
                        Section::make('Color Scheme')
                            ->icon('heroicon-m-swatch')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        ColorPicker::make('branding.main_color')
                                            ->label('Main Color')
                                            ->helperText('Primary brand color for the booking interface')
                                            ->default('#3B82F6'),
                                        ColorPicker::make('branding.secondary_color')
                                            ->label('Secondary Color')
                                            ->helperText('Secondary brand color for accents and highlights')
                                            ->default('#1E40AF'),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        ColorPicker::make('branding.gradient_start')
                                            ->label('Gradient Start Color')
                                            ->helperText('Start color for gradient backgrounds')
                                            ->default('#3B82F6'),
                                        ColorPicker::make('branding.gradient_end')
                                            ->label('Gradient End Color')
                                            ->helperText('End color for gradient backgrounds')
                                            ->default('#1E40AF'),
                                    ]),
                                ColorPicker::make('branding.text_color')
                                    ->label('Text Color')
                                    ->helperText('Standard text color for the booking interface')
                                    ->default('#1F2937')
                                    ->columnSpan(1),
                            ])
                            ->collapsible(),
                    ])
                    ->collapsible(),
                Section::make('Venue Collection')
                    ->icon('heroicon-m-rectangle-stack')
                    ->description('Manage curated venues for this concierge')
                    ->visible(fn (Get $get): bool => $get('can_manage_own_collections') === true)
                    ->schema([
                        Select::make('collection_region_id')
                            ->label('Region')
                            ->options(Region::active()->pluck('name', 'id'))
                            ->placeholder('Select Region')
                            ->helperText('Select the region for this venue collection')
                            ->searchable()
                            ->required()
                            ->reactive(),
                        Toggle::make('collection_is_active')
                            ->label('Active'),
                        TextInput::make('collection_name')
                            ->label('Collection Title')
                            ->helperText('Public-facing title for this curated collection')
                            ->placeholder('Curated Dining Experiences')
                            ->columnSpanFull(),
                        Textarea::make('collection_description')
                            ->label('Collection Description')
                            ->helperText('Brief description of this curated collection')
                            ->placeholder('Handpicked dining experiences by our expert curator')
                            ->rows(2)
                            ->columnSpanFull(),
                        Repeater::make('collection_venues')
                            ->label('Venues in Collection')
                            ->schema([
                                Select::make('venue_id')
                                    ->label('Venue')
                                    ->options(function (Get $get) {
                                        $regionId = $get('../../collection_region_id');
                                        $query = Venue::query()->where('status', VenueStatus::ACTIVE);

                                        if ($regionId) {
                                            $query->where('region', $regionId);
                                        }

                                        // Get currently selected venue IDs to exclude them from options
                                        $currentVenues = collect($get('../../collection_venues'))
                                            ->pluck('venue_id')
                                            ->filter()
                                            ->toArray();

                                        return $query->whereNotIn('id', $currentVenues)
                                            ->pluck('name', 'id');
                                    })
                                    ->getOptionLabelUsing(fn ($value): ?string => Venue::query()->find($value)?->name)
                                    ->searchable()
                                    ->required()
                                    ->reactive(),
                                Textarea::make('note')
                                    ->label('Note/Review')
                                    ->rows(2)
                                    ->placeholder('Add a note, review, or recommendation...'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->orderable()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (! isset($state['venue_id'])) {
                                    return 'Unknown Venue';
                                }

                                $venue = Venue::query()->find($state['venue_id']);

                                return $venue ? $venue->name : 'Unknown Venue (ID: '.$state['venue_id'].')';
                            })
                            ->addActionLabel('Add Venue')
                            ->reorderableWithButtons()
                            ->cloneable()
                            ->collapsible(),
                    ])
                    ->collapsible(),

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        $concierge = $this->getRecord();

        // Load existing venue collection data using shared trait
        $collectionData = $this->loadVenueCollectionData($concierge);

        // Load branding data
        $brandingData = $concierge->branding?->toArray() ?? [];

        $this->form->fill([
            'hotel_name' => $concierge->hotel_name,
            'is_qr_concierge' => $concierge->is_qr_concierge,
            'revenue_percentage' => $concierge->revenue_percentage,
            'can_override_duplicate_checks' => $concierge->can_override_duplicate_checks,
            'can_manage_own_branding' => $concierge->can_manage_own_branding ?? false,
            'can_manage_own_collections' => $concierge->can_manage_own_collections ?? false,
            'can_manage_vip_branding' => $concierge->can_manage_vip_branding ?? false,
            'can_manage_vip_collections' => $concierge->can_manage_vip_collections ?? false,
            'collection_is_active' => $collectionData['collection_is_active'],
            'collection_region_id' => $collectionData['collection_region_id'],
            'collection_venues' => $collectionData['collection_venues'],
            'collection_name' => $collectionData['collection_name'],
            'collection_description' => $collectionData['collection_description'],
            'branding' => $brandingData,
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure logo file is set to public visibility
        if (isset($data['branding']['logo_url']) && $data['branding']['logo_url']) {
            Storage::disk('do')->setVisibility($data['branding']['logo_url'], 'public');
        }

        // Handle venue collection data
        if (isset($data['collection_venues']) && is_array($data['collection_venues'])) {
            try {
                $this->saveVenueCollection($data, $this->getRecord());
            } catch (Exception $e) {
                Log::error('Failed to save venue collection', [
                    'concierge_id' => $this->getRecord()->id,
                    'data' => $data,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return $data;
    }
}
