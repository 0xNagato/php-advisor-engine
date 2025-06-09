<?php

namespace App\Filament\Pages;

use App\Enums\VenueStatus;
use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Region;
use App\Models\Specialty;
use App\Models\Venue;
use Exception;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

class BulkEditVenues extends Page
{
    protected static string $view = 'filament.pages.bulk-edit-venues';

    protected static ?string $navigationLabel = 'Bulk Edit Venues';

    protected static ?string $title = 'Bulk Edit Venues';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Venues';

    protected static ?int $navigationSort = 2;

    // Filter properties
    public ?string $statusFilter = null;

    public ?string $regionFilter = null;

    public ?string $searchFilter = null;

    public int $perPage = 20;

    public int $currentPage = 1;

    // Form data for venues
    public array $venuesData = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasActiveRole(['super_admin']);
    }

    public function mount(): void
    {
        $this->clearComputedCache();
        $this->loadVenues();
    }

    #[Computed]
    public function availableRegions(): array
    {
        return Region::all()->pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function availableStatuses(): array
    {
        return [
            VenueStatus::ACTIVE->value => 'Active',
            VenueStatus::DRAFT->value => 'Draft',
            VenueStatus::PENDING->value => 'Pending',
            VenueStatus::SUSPENDED->value => 'Suspended',
            VenueStatus::HIDDEN->value => 'Hidden',
            VenueStatus::UPCOMING->value => 'Upcoming',
        ];
    }

    #[Computed]
    public function venues()
    {
        $query = Venue::query()
            ->with(['inRegion'])
            ->orderBy('name');

        // Apply filters
        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (filled($this->regionFilter)) {
            $query->where('region', $this->regionFilter);
        }

        if (filled($this->searchFilter)) {
            $query->where('name', 'ilike', '%'.$this->searchFilter.'%');
        }

        return $query->paginate($this->perPage, ['*'], 'page', $this->currentPage);
    }

    public function loadVenues(): void
    {
        // Load venues data

        $venues = $this->venues;
        $this->venuesData = [];

        foreach ($venues as $venue) {
            // Refresh venue from the database to ensure we have the latest data
            $freshVenue = Venue::query()->find($venue->id);

            $this->venuesData[$venue->id] = [
                'address' => $freshVenue->address ?? '',
                'description' => $freshVenue->description ?? '',
                'images' => $freshVenue->images ?? [],
                'neighborhood' => $freshVenue->neighborhood,
                'cuisines' => $freshVenue->cuisines ?? [],
                'specialty' => $freshVenue->specialty ?? [],
            ];

        }
    }

    public function applyFilters(): void
    {
        $this->currentPage = 1;
        $this->loadVenues();
    }

    public function resetFilters(): void
    {
        $this->statusFilter = null;
        $this->regionFilter = null;
        $this->searchFilter = null;
        $this->currentPage = 1;
        $this->loadVenues();
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadVenues();
        }
    }

    public function nextPage(): void
    {
        $venues = $this->venues;
        if ($venues->hasMorePages()) {
            $this->currentPage++;
            $this->loadVenues();
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
        $this->loadVenues();
    }

    public function form(Form $form): Form
    {
        $venues = $this->venues;
        $formSchema = [];

        foreach ($venues as $venue) {
            $formSchema[] = Section::make($venue->name)
                ->description($venue->formattedRegion.($venue->formattedNeighborhood ? ' • '.$venue->formattedNeighborhood : ''))
                ->icon('heroicon-m-building-storefront')
                ->schema([
                    Group::make([
                        Textarea::make("venuesData.{$venue->id}.address")
                            ->label('Address')
                            ->rows(3)
                            ->placeholder("123 Main Street\nNew York, NY 10001\nUnited States")
                            ->columnSpan(1),

                        Textarea::make("venuesData.{$venue->id}.description")
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Enter venue description...')
                            ->columnSpan(1),
                    ])->columns(2),

                    FileUpload::make("venuesData.{$venue->id}.images")
                        ->label('Images')
                        ->disk('do')
                        ->directory(app()->environment().'/venues/images')
                        ->moveFiles()
                        ->multiple()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8192)
                        ->maxFiles(5)
                        ->imagePreviewHeight('80')
                        ->columnSpanFull(),

                    Group::make([
                        Select::make("venuesData.{$venue->id}.neighborhood")
                            ->label('Neighborhood')
                            ->options(function () use ($venue) {
                                if (! $venue->region) {
                                    return [];
                                }

                                return Neighborhood::query()
                                    ->where('region', $venue->region)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->columnSpan(1),

                        Select::make("venuesData.{$venue->id}.specialty")
                            ->label('Specialties')
                            ->options(function () use ($venue) {
                                if (! $venue->region) {
                                    return [];
                                }

                                return Specialty::getSpecialtiesByRegion($venue->region);
                            })
                            ->multiple()
                            ->searchable()
                            ->columnSpan(1),
                    ])->columns(2),

                    CheckboxList::make("venuesData.{$venue->id}.cuisines")
                        ->label('Cuisines')
                        ->options(Cuisine::query()->pluck('name', 'id'))
                        ->searchable()
                        ->gridDirection('row')
                        ->columns(6)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed()
                ->persistCollapsed()
                ->compact()
                ->id("venue-{$venue->id}");
        }

        return $form->schema($formSchema);
    }

    public function save(): void
    {
        $this->validate();

        $updatedCount = 0;
        $errorCount = 0;

        DB::beginTransaction();

        try {
            foreach ($this->venuesData as $venueId => $data) {
                $venue = Venue::query()->find($venueId);

                if (! $venue) {
                    continue;
                }

                try {
                    // Handle file uploads manually like VenueOnboarding
                    $existingImages = $venue->images ?? [];
                    $newImages = [];

                    // Process uploaded images if any
                    if (isset($data['images']) && filled($data['images'])) {
                        $imageFiles = is_array($data['images']) ? $data['images'] : [$data['images']];

                        foreach ($imageFiles as $imageFile) {
                            if (is_object($imageFile) && method_exists($imageFile, 'storeAs')) {
                                // This is a TemporaryUploadedFile - manually store it
                                try {
                                    $fileName = $venue->slug.'-'.time().'-'.uniqid().'.'.$imageFile->getClientOriginalExtension();

                                    $path = $imageFile->storeAs(
                                        app()->environment().'/venues/images',
                                        $fileName,
                                        ['disk' => 'do']
                                    );

                                    // Set visibility to public
                                    Storage::disk('do')->setVisibility($path, 'public');

                                    $newImages[] = $path;

                                } catch (Exception $e) {
                                    logger()->error('Failed to store uploaded file', [
                                        'venue_id' => $venue->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            } elseif (is_string($imageFile) && filled($imageFile)) {
                                // Existing file path - keep it
                                if (Storage::disk('do')->exists($imageFile)) {
                                    $newImages[] = $imageFile;
                                }
                            }
                        }
                    }

                    // Combine existing and new images
                    $finalImages = array_merge($existingImages, $newImages);

                    // Update venue
                    $venue->update([
                        'address' => $data['address'] ?? $venue->address,
                        'description' => $data['description'] ?? $venue->description,
                        'images' => $finalImages,
                        'neighborhood' => $data['neighborhood'] ?? $venue->neighborhood,
                        'cuisines' => $data['cuisines'] ?? $venue->cuisines ?? [],
                        'specialty' => $data['specialty'] ?? $venue->specialty ?? [],
                    ]);

                    $updatedCount++;

                    // Log the activity
                    activity()
                        ->performedOn($venue)
                        ->withProperties([
                            'bulk_edit' => true,
                            'updated_fields' => array_keys(array_filter($data, fn ($value) => filled($value))),
                            'updated_by' => auth()->user()->name,
                        ])
                        ->log('Venue bulk edited');

                } catch (Exception $e) {
                    $errorCount++;
                    logger()->error("Failed to update venue {$venue->name}: ".$e->getMessage());
                }
            }

            DB::commit();

            if ($updatedCount > 0) {
                Notification::make()
                    ->success()
                    ->title('Venues Updated Successfully')
                    ->body("Updated {$updatedCount} venues".($errorCount > 0 ? " with {$errorCount} errors" : ''))
                    ->send();
            }

            if ($errorCount > 0 && $updatedCount === 0) {
                Notification::make()
                    ->danger()
                    ->title('Update Failed')
                    ->body("Failed to update {$errorCount} venues. Please check the logs.")
                    ->send();
            }

            // Clear computed property cache and reload venues to show updated data
            $this->clearComputedCache();
            $this->loadVenues();

            // Ensure all images are public after save
            $this->makeAllImagesPublic();

        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Bulk Update Failed')
                ->body('An error occurred: '.$e->getMessage())
                ->send();
        }
    }

    /**
     * Make all venue images public after saving (similar to EditVenue afterSave)
     */
    private function makeAllImagesPublic(): void
    {
        foreach ($this->venues as $venue) {
            if ($venue->images && is_array($venue->images)) {
                foreach ($venue->images as $imagePath) {
                    if ($imagePath && Storage::disk('do')->exists($imagePath)) {
                        Storage::disk('do')->setVisibility($imagePath, 'public');
                    }
                }
            }
        }
    }

    public function resetChanges(): void
    {
        $this->clearComputedCache();
        $this->loadVenues();

        Notification::make()
            ->info()
            ->title('Changes Reset')
            ->body('All unsaved changes have been reset.')
            ->send();
    }

    private function clearComputedCache(): void
    {
        // Force a refresh by re-running the query instead of relying on computed cache clearing
        // This ensures we get fresh data from the database
    }
}
