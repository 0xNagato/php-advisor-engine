<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantProfileResource\Pages;
use App\Filament\Resources\RestaurantProfileResource\RelationManagers;
use App\Models\RestaurantProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RestaurantProfileResource extends Resource
{
    protected static ?string $model = RestaurantProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('restaurant_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website_url')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cuisines')
                    ->required(),
                Forms\Components\TextInput::make('price_range')
                    ->required(),
                Forms\Components\TextInput::make('sunday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('monday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('tuesday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('wednesday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('thursday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('friday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('saturday_hours_of_operation')
                    ->required(),
                Forms\Components\TextInput::make('address_line_1')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address_line_2')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('city')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('state')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('zip')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restaurant_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantProfiles::route('/'),
            'create' => Pages\CreateRestaurantProfile::route('/create'),
            'view' => Pages\ViewRestaurantProfile::route('/{record}'),
            'edit' => Pages\EditRestaurantProfile::route('/{record}/edit'),
        ];
    }
}
