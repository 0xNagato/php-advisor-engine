<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages\CreateRestaurant;
use App\Filament\Resources\RestaurantResource\Pages\EditRestaurant;
use App\Filament\Resources\RestaurantResource\Pages\ListRestaurants;
use App\Filament\Resources\RestaurantResource\Pages\ViewRestaurant;
use App\Models\Region;
use App\Models\Restaurant;
use App\Traits\ImpersonatesOther;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    use ImpersonatesOther;

    protected static ?string $model = Restaurant::class;

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
            ->recordUrl(fn (Restaurant $record) => ViewRestaurant::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('restaurant_name')
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
                    ->action(fn (Restaurant $record) => $this->impersonate($record->user)),
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
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'view' => ViewRestaurant::route('/{record}'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}
