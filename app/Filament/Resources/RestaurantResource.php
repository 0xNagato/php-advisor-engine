<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages\CreateRestaurant;
use App\Filament\Resources\RestaurantResource\Pages\EditRestaurant;
use App\Filament\Resources\RestaurantResource\Pages\ListRestaurants;
use App\Filament\Resources\RestaurantResource\Pages\ViewRestaurant;
use App\Models\Region;
use App\Models\Restaurant;
use Exception;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
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
        return $table
            ->columns([
                TextColumn::make('restaurant_name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(['first_name', 'last_name'])
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
