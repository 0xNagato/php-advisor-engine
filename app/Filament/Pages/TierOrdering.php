<?php

namespace App\Filament\Pages;

use App\Models\Region;
use App\Models\Venue;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class TierOrdering extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?string $navigationLabel = 'Tier Ordering';

    protected static ?string $title = 'Tier Ordering';

    protected static ?string $navigationGroup = 'Venues';

    protected static ?int $navigationSort = 60;

    protected static string $view = 'filament.pages.tier-ordering';

    public ?string $region = null;

    public ?int $tier = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->region = null;
        $this->tier = null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filters')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('region')
                                    ->label('Region')
                                    ->options(Region::query()->pluck('name', 'id')->toArray())
                                    ->native(false)
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state): void {
                                        $this->region = $state ?: null;
                                        $this->resetTable();
                                    }),
                                Select::make('tier')
                                    ->label('Tier')
                                    ->options([
                                        1 => 'Gold',
                                        2 => 'Silver',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state): void {
                                        $this->tier = $state ? (int) $state : null;
                                        $this->resetTable();
                                    }),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): EloquentBuilder {
                $query = Venue::query();

                if ($this->region && $this->tier) {
                    $query->where('region', $this->region)
                        ->where('tier', $this->tier);
                } else {
                    // Prevent any records from showing until both filters are selected
                    $query->whereRaw('1 = 0');
                }

                return $query;
            })
            ->reorderable('tier_position')
            ->columns([
                TextColumn::make('name')
                    ->label('Venue')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tier_position')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('region')
                    ->label('Region')
                    ->formatStateUsing(fn (Venue $record): string => $record->formattedRegion ?: $record->region),
            ])
            ->defaultSort('tier_position', 'asc')
            ->emptyStateHeading('Select Region and Tier')
            ->emptyStateDescription('Choose a region and tier above to load venues, then drag to reorder.')
            ->paginated(false);
    }
}
