<?php

namespace App\Livewire\Concierge;

use App\Actions\VipCode\GenerateVipReferralQRCode;
use App\Data\AffiliateBrandingData;
use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Enums\VipCodeTemplate;
use App\Models\Region;
use App\Models\Venue;
use App\Models\VipCode;
use App\Services\CurrencyConversionService;
use App\Traits\ManagesVenueCollections;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\HasFilters;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VipCodesTable extends TableWidget
{
    use HasFilters;
    use ManagesVenueCollections;

    public static ?string $heading = 'VIP Codes';

    protected static bool $isLazy = true;

    public int|string|array $columnSpan;

    protected $listeners = ['concierge-referred' => '$refresh'];

    const bool USE_SLIDE_OVER = false;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->tableFilters['startDate'];
        $endDate = $this->tableFilters['endDate'];

        $query = VipCode::with([
            'concierge.user',
            'earnings' => function ($query) use ($startDate, $endDate) {
                $query->whereNotNull('earnings.confirmed_at')
                    ->whereBetween('earnings.created_at', [$startDate, $endDate])
                    ->get(['amount', 'earnings.currency']);
            },
        ])
            ->withCount([
                'bookings' => function (Builder $query) use ($startDate, $endDate) {
                    $query->whereIn('status', BookingStatus::REPORTING_STATUSES)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                },
            ]);

        if (auth()->user()->hasActiveRole('concierge')) {
            $query->where('concierge_id', auth()->user()->concierge->id);
        }

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                TextColumn::make('concierge.user.name')
                    ->size('xs')
                    ->label('User')->visible(fn () => auth()->user()->hasActiveRole('super_admin')),
                TextColumn::make('code')
                    ->label('Code')
                    ->copyable()
                    ->size('xs')
                    ->copyMessage('VIP Link copied to clipboard')
                    ->copyableState(fn (VipCode $vipCode) => $vipCode->link)
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-clipboard-document-check')
                    ->iconColor('primary')
                    ->iconPosition(IconPosition::After),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->visibleFrom('sm')
                    ->alignCenter()
                    ->size('xs'),
                TextColumn::make('earnings')
                    ->label('Earned')
                    ->size('xs')
                    ->alignRight()
                    ->formatStateUsing(function (VipCode $vipCode): string {
                        $byCurrency = $vipCode->earnings->groupBy('currency')
                            ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)
                            ->toArray();
                        $currencyService = app(CurrencyConversionService::class);

                        $inUsd = $currencyService->convertToUSD($byCurrency);

                        return money($inUsd, 'USD');
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')->date('M jS, Y')
                    ->label('Created')
                    ->visibleFrom('sm')
                    ->size('xs'),
            ])
            ->actions([
                Action::make('viewQR')
                    ->iconButton()
                    ->icon('tabler-qrcode')
                    ->size('xs')
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->modalHeading('')
                    ->modalContent(function (VipCode $vipCode): HtmlString {
                        $qrCodeData = $this->getQr($vipCode);
                        $showPrintButton = config('app.features.show_qr_code_print_button', false);

                        $printButton = '';
                        if ($showPrintButton) {
                            $printUrl = route('vip-code.print', [
                                'code' => $vipCode->code,
                                'svg_path' => $qrCodeData['svgPath'],
                            ]);

                            $printButton = <<<HTML
                                <a href="{$printUrl}"
                                target="_blank"
                                class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-semibold text-white transition-colors bg-green-600 rounded-md shadow-sm hover:bg-green-700 sm:text-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print QR Code
                                </a>
                            HTML;
                        }

                        return new HtmlString(<<<HTML
                                    <div class="flex flex-col items-center">
                                      <img src="{$qrCodeData['image']}" alt="{$vipCode->code} QR Code" class="mb-4 w-85 h-85">
                                      <div class="flex flex-col w-full max-w-xs mt-2 space-y-3">
                                        <a href="{$qrCodeData['downloadURL']}" download="prima-referral-{$vipCode->code}.svg"
                                          class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-semibold text-white transition-colors bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-700 sm:text-base">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                          </svg>
                                          Download QR Code
                                        </a>
                                        {$printButton}
                                      </div>
                                    </div>
                                HTML
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                Action::make('viewVipBookings')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading('VIP Bookings')
                    ->modalContent(fn (VipCode $vipCode) => view(
                        'partials.vip-code-modal-view',
                        ['vipCode' => $vipCode]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER)
                    ->size('xs'),
                Action::make('manageBranding')
                    ->iconButton()
                    ->icon('heroicon-m-paint-brush')
                    ->modalHeading('Manage Branding')
                    ->form([
                        Section::make('Brand Information')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('brand_name')
                                            ->label('Brand Name')
                                            ->helperText('The name of your brand for the booking experience')
                                            ->placeholder('Your Brand Name'),
                                        TextInput::make('redirect_url')
                                            ->label('Redirect URL')
                                            ->helperText('Optional URL to redirect users after booking completion')
                                            ->url()
                                            ->placeholder('https://example.com/thank-you'),
                                    ]),
                                Select::make('template')
                                    ->label('Booking Template')
                                    ->options(VipCodeTemplate::options())
                                    ->default(VipCodeTemplate::AVAILABILITY_CALENDAR->value)
                                    ->helperText('Booking template for this VIP code')
                                    ->columnSpan(1),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->helperText('Description for the booking experience')
                                    ->placeholder('Welcome to our exclusive booking experience...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Visual Branding')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('logo_url')
                                    ->label('Logo')
                                    ->helperText('Upload a logo for the white-labeled booking experience')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('do')
                                    ->directory(app()->environment().'/vip-codes/logos')
                                    ->moveFiles()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(8192)
                                    ->imagePreviewHeight('254')
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file, Get $get, VipCode $record) => $record->code.'-logo-'.time().'.'.$file->getClientOriginalExtension()
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),
                        Section::make('Influencer Information')
                            ->icon('heroicon-m-user-circle')
                            ->description('Optional influencer/curator details for personalized experience')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('influencer_name')
                                            ->label('Influencer Name')
                                            ->helperText('Name of the influencer or curator')
                                            ->placeholder('John Doe'),
                                        TextInput::make('influencer_handle')
                                            ->label('Social Handle')
                                            ->helperText('Social media handle (without @)')
                                            ->placeholder('johndoe'),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('follower_count')
                                            ->label('Follower Count')
                                            ->helperText('Number of followers (e.g., 12.5K, 1M)')
                                            ->placeholder('12.5K'),
                                        TextInput::make('social_url')
                                            ->label('Social Media URL')
                                            ->helperText('Link to social media profile')
                                            ->url()
                                            ->placeholder('https://instagram.com/johndoe'),
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(),
                        Section::make('Color Scheme')
                            ->icon('heroicon-m-swatch')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        ColorPicker::make('main_color')
                                            ->label('Main Color')
                                            ->helperText('Primary brand color for the booking interface'),
                                        ColorPicker::make('secondary_color')
                                            ->label('Secondary Color')
                                            ->helperText('Secondary brand color for accents and highlights'),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        ColorPicker::make('gradient_start')
                                            ->label('Gradient Start Color')
                                            ->helperText('Start color for gradient backgrounds'),
                                        ColorPicker::make('gradient_end')
                                            ->label('Gradient End Color')
                                            ->helperText('End color for gradient backgrounds'),
                                    ]),
                                ColorPicker::make('text_color')
                                    ->label('Text Color')
                                    ->helperText('Standard text color for the booking interface')
                                    ->columnSpan(1),
                            ])
                            ->collapsible(),
                    ])
                    ->fillForm(function (VipCode $record): array {
                        $brandingData = [];
                        if ($record->branding !== null) {
                            $record->refresh();
                            if ($record->branding instanceof AffiliateBrandingData) {
                                $brandingData = $record->branding->toArray();
                            }
                        }

                        return $brandingData;
                    })
                    ->action(function (VipCode $record, array $data): void {
                        try {
                            // Ensure logo file is set to public visibility
                            if (isset($data['logo_url']) && $data['logo_url']) {
                                Storage::disk('do')->setVisibility($data['logo_url'], 'public');
                            }

                            // Filter out empty values
                            $brandingData = array_filter($data, fn ($value) => ! empty($value));

                            $record->update([
                                'branding' => $brandingData ?: null,
                            ]);

                            Notification::make()
                                ->title('Branding Updated')
                                ->body('VIP code branding has been updated successfully.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('Error saving VIP code branding', [
                                'vip_code_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Error Saving Branding')
                                ->body('There was an error saving the branding. Please try again.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->slideOver(self::USE_SLIDE_OVER)
                    ->size('xs')
                    ->visible(fn (VipCode $vipCode) => auth()->user()->concierge->can_manage_vip_branding ?? false),
                Action::make('manageCollections')
                    ->iconButton()
                    ->icon('heroicon-m-rectangle-stack')
                    ->modalHeading('Manage Venue Collections')
                    ->form([
                        Section::make('Venue Collection')
                            ->icon('heroicon-m-rectangle-stack')
                            ->description('Manage curated venues for this VIP code')
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
                                    ->itemLabel(function (array $state): string {
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
                    ])
                    ->fillForm(function (VipCode $record): array {
                        // Load existing venue collection data using shared trait
                        return $this->loadVenueCollectionData($record);
                    })
                    ->action(function (VipCode $record, array $data): void {
                        try {
                            if (isset($data['collection_venues']) && is_array($data['collection_venues'])) {
                                $this->saveVenueCollection($data, $record);

                                Notification::make()
                                    ->title('Collection Updated')
                                    ->body('Venue collection has been updated successfully.')
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Log::error('Error saving venue collection', [
                                'vip_code_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Error Saving Collection')
                                ->body('There was an error saving the venue collection. Please try again.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->slideOver(self::USE_SLIDE_OVER)
                    ->size('xs')
                    ->visible(fn (VipCode $vipCode) => auth()->user()->concierge->can_manage_vip_collections ?? false),
            ])
            ->recordUrl(null)
            ->recordAction(fn (): string => 'viewVipBookings')
            ->defaultSortOptionLabel('Created')
            ->defaultSort('created_at', 'desc');
    }

    private function getQr(VipCode $vipCode): array
    {
        return app(GenerateVipReferralQRCode::class)->execute($vipCode);
    }
}
