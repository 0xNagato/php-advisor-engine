<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenueResource\Pages\CreateVenue;
use App\Filament\Resources\VenueResource\Pages\EditVenue;
use App\Filament\Resources\VenueResource\Pages\ListVenues;
use App\Filament\Resources\VenueResource\Pages\ViewVenue;
use App\Models\Region;
use App\Models\Venue;
use App\Traits\ImpersonatesOther;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VenueResource extends Resource
{
    use ImpersonatesOther;

    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = -1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return (new self)->configureTable($table);
    }

    /**
     * @throws Exception
     */
    public function configureTable(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Venue $record) => ViewVenue::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('partnerReferral.user.name')
                    ->label('Partner'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Venue $record) => $this->impersonate($record->user))
                    ->hidden(fn () => isPrimaApp()),
                EditAction::make()
                    ->iconButton(),
            ])
            ->paginated([5, 10, 25])
            ->filters([
                SelectFilter::make('region')
                    ->options(Region::query()->pluck('name', 'id')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVenues::route('/'),
            'create' => CreateVenue::route('/create'),
            'view' => ViewVenue::route('/{record}'),
            'edit' => EditVenue::route('/{record}/edit'),
        ];
    }
}
