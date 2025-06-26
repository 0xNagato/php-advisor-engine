<?php

namespace App\Filament\Resources\Venue;

use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\CreatePlatform;
use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\EditPlatform;
use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\ListPlatforms;
use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\ViewReservations;
use App\Models\VenuePlatform;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingPlatformsResource extends Resource
{
    protected static ?string $model = VenuePlatform::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Booking Platforms';

    protected static ?string $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 3;

    // Define a simpler slug for the resource
    protected static ?string $slug = 'booking-platforms';

    protected static ?string $modelLabel = 'Platform Connection';

    protected static ?string $pluralModelLabel = 'Platform Connections';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('venue'))
            ->columns([
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('platform_type')
                    ->label('Connected Platform')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'covermanager' => 'CoverManager',
                        'restoo' => 'Restoo',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                IconColumn::make('is_enabled')
                    ->label('Connection Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('reservations_count')
                    ->label('Reservations')
                    ->getStateUsing(function (VenuePlatform $record): int {
                        if ($record->platform_type === 'covermanager') {
                            return $record->venue->coverManagerReservations()->count();
                        } elseif ($record->platform_type === 'restoo') {
                            return $record->venue->restooReservations()->count();
                        }

                        return 0;
                    })
                    ->sortable(false)
                    ->alignCenter()
                    ->url(fn (VenuePlatform $record): string => static::getUrl('view-reservations', ['record' => $record->id]))
                    ->color('primary'),
            ])
            ->filters([
                SelectFilter::make('platform_type')
                    ->label('Platform Type')
                    ->options([
                        'covermanager' => 'CoverManager',
                        'restoo' => 'Restoo',
                    ]),
                SelectFilter::make('is_enabled')
                    ->label('Connection Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('Edit Connection'),
            ])
            ->defaultSort('venue.name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatforms::route('/'),
            'create' => CreatePlatform::route('/create'),
            'edit' => EditPlatform::route('/{record}/edit'),
            'view-reservations' => ViewReservations::route('/{record}/reservations'),
        ];
    }
}
