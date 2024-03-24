<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConciergeResource\Pages\CreateConcierge;
use App\Filament\Resources\ConciergeResource\Pages\EditConcierge;
use App\Filament\Resources\ConciergeResource\Pages\ListConcierges;
use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Concierge;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConciergeResource extends Resource
{
    protected static ?string $model = Concierge::class;

    protected static ?string $navigationIcon = 'govicon-user-suit';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('hotel_name')
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

            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConcierges::route('/'),
            'create' => CreateConcierge::route('/create'),
            'view' => ViewConcierge::route('/{record}'),
            'edit' => EditConcierge::route('/{record}/edit'),
        ];
    }
}
