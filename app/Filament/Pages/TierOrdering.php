<?php

namespace App\Filament\Pages;

use App\Models\Region;
use App\Models\Venue;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class TierOrdering extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?string $navigationLabel = 'Tier Ordering';

    protected static ?string $title = 'Tier Ordering';

    protected static ?string $navigationGroup = 'Venues';

    protected static ?int $navigationSort = 60;

    protected static string $view = 'filament.pages.tier-ordering';

    public ?string $region = null;

    public ?int $tier = 1;

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
        $this->region = $this->region ?? config('app.default_region', 'miami');
        $this->tier = $this->tier ?? 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): EloquentBuilder => Venue::query()
                ->where('region', $this->region)
                ->where('tier', $this->tier)
            )
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
            ->filters([
                SelectFilter::make('region')
                    ->label('Region')
                    ->options(Region::query()->pluck('name', 'id')->toArray())
                    ->default($this->region)
                    ->indicator('Region')
                    ->query(function (EloquentBuilder $query, array $data) {
                        if (! empty($data['value'])) {
                            $this->region = (string) $data['value'];
                            $query->where('region', $this->region);
                        }
                    }),
                SelectFilter::make('tier')
                    ->label('Tier')
                    ->options([
                        1 => 'Gold',
                        2 => 'Silver',
                    ])
                    ->default($this->tier)
                    ->indicator('Tier')
                    ->query(function (EloquentBuilder $query, array $data) {
                        if (! empty($data['value'])) {
                            $this->tier = (int) $data['value'];
                            $query->where('tier', $this->tier);
                        }
                    }),
            ])
            ->paginated(false);
    }
}
