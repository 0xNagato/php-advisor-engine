<?php

namespace App\Filament\Pages;

use App\Enums\VenueStatus;
use App\Models\Cuisine;
use App\Models\Neighborhood;
use App\Models\Region;
use App\Models\Specialty;
use App\Models\Venue;
use Exception;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Throwable;

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
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . $this->searchFilter . '%']);
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
                'vat' => $freshVenue->vat ?? '',
                'description' => $freshVenue->description ?? '',
                'images' => $freshVenue->images ?? [],
                'neighborhood' => $freshVenue->neighborhood,
                'cuisines' => $freshVenue->cuisines ?? [],
                'specialty' => $freshVenue->specialty ?? [],
            ];
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['statusFilter', 'regionFilter', 'searchFilter', 'perPage', 'currentPage'])) {
            $this->currentPage = 1;
            $this->loadVenues();
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
                ->description($venue->formattedRegion . ($venue->formattedNeighborhood ? ' • ' . $venue->formattedNeighborhood : ''))
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
                    ])->columns(),

                    Group::make([
                        TextInput::make("venuesData.{$venue->id}.vat")
                            ->label('VAT Number')
                            ->maxLength(100)
                            ->placeholder('e.g., GB123456789')
                            ->columnSpan(1),
                    ])->columns(),

                    FileUpload::make("venuesData.{$venue->id}.images")
                        ->label('Images')
                        ->disk('do')
                        ->directory(app()->environment() . '/venues/images')
                        ->moveFiles()
                        ->multiple()
                        ->imageEditor()
                        ->image()
                        ->maxSize(8192)
                        ->maxFiles(5)
                        ->imagePreviewHeight('80')
                        ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file): ?array {
                            return [
                                'name' => basename($file),
                                'size' => 0,
                                'type' => null,
                                'url' => $file,
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
                            // Nothing to return; Filament will remove it from the form state.
                        })
                        ->columnSpanFull(),

                    Group::make([
                        Select::make("venuesData.{$venue->id}.neighborhood")
                            ->label('Neighborhood')
                            ->options(function () use ($venue) {
                                if (!$venue->region) {
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
                                if (!$venue->region) {
                                    return [];
                                }

                                return Specialty::getSpecialtiesByRegion($venue->region);
                            })
                            ->multiple()
                            ->searchable()
                            ->columnSpan(1),
                    ])->columns(),

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

                if (!$venue) {
                    continue;
                }

                try {
                    $updates = [
                        'address' => $data['address'] ?? $venue->address,
                        'vat' => $data['vat'] ?? $venue->vat,
                        'description' => $data['description'] ?? $venue->description,
                        'neighborhood' => $data['neighborhood'] ?? $venue->neighborhood,
                        'cuisines' => $data['cuisines'] ?? $venue->cuisines ?? [],
                        'specialty' => $data['specialty'] ?? $venue->specialty ?? [],
                    ];

                    /**
                     * The 'images' key in $data can take several forms:
                     * - []: An empty array (no images submitted)
                     * - array of strings: Existing image paths retained by the user
                     * - array with TemporaryUploadedFile objects: Newly uploaded images
                     * - single string or TemporaryUploadedFile: A single image, either existing or new
                     *
                     * The following logic normalizes these possibilities into a consistent array of image paths.
                     */
                    if (array_key_exists('images', $data)) {
                        $submitted = $data['images'];
                        $finalImages = [];
                        $keptExisting = []; // existing paths the user kept (strings passed back)

                        $disk = Storage::disk('do');

                        $items = is_array($submitted) ? $submitted : (filled($submitted) ? [$submitted] : []);

                        foreach ($items as $item) {
                            if (is_object($item) && method_exists($item, 'storeAs')) {
                                // New upload
                                try {
                                    $fileName = $venue->slug . '-' . time() . '-' . uniqid() . '.' . $item->getClientOriginalExtension();
                                    $path = $item->storeAs(
                                        app()->environment() . '/venues/images',
                                        $fileName,
                                        ['disk' => 'do']
                                    );
                                    $disk->setVisibility($path, 'public');
                                    $finalImages[] = $path;
                                } catch (Exception $e) {
                                    logger()->error('Failed to store uploaded file', [
                                        'venue_id' => $venue->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            } elseif (is_string($item) && filled($item)) {
                                // Existing path retained
                                $normalized = ltrim(parse_url($item, PHP_URL_PATH) ?: $item, '/');
                                if ($disk->exists($normalized)) {
                                    $finalImages[] = $normalized;
                                    $keptExisting[] = $normalized;
                                }
                            }
                        }

                        // Delete removed images (those that were previously stored but not kept)
                        $previousImages = $venue->images ?? [];
                        $removed = array_diff($previousImages, $keptExisting);
                        foreach ($removed as $removedPath) {
                            try {
                                if ($disk->exists($removedPath)) {
                                    $disk->delete($removedPath);
                                }
                            } catch (Throwable $e) {
                                logger()->warning('Failed to delete removed image', [
                                    'venue_id' => $venue->id,
                                    'path' => $removedPath,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // If user deleted all, $finalImages will be []
                        $updates['images'] = $finalImages;
                    }

                    // Update venue
                    $venue->update($updates);

                    $updatedCount++;

                    // Log the activity
                    activity()
                        ->performedOn($venue)
                        ->withProperties([
                            'bulk_edit' => true,
                            'updated_fields' => array_keys(array_filter($data, fn($value) => filled($value))),
                            'updated_by' => auth()->user()->name,
                        ])
                        ->log('Venue bulk edited');
                } catch (Exception $e) {
                    $errorCount++;
                    logger()->error("Failed to update venue {$venue->name}: " . $e->getMessage());
                }
            }

            DB::commit();

            if ($updatedCount > 0) {
                Notification::make()
                    ->success()
                    ->title('Venues Updated Successfully')
                    ->body("Updated {$updatedCount} venues" . ($errorCount > 0 ? " with {$errorCount} errors" : ''))
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
                ->body('An error occurred: ' . $e->getMessage())
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
