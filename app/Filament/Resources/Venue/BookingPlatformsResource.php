<?php

namespace App\Filament\Resources\Venue;

use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\CreatePlatform;
use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\EditPlatform;
use App\Filament\Resources\Venue\BookingPlatformsResource\Pages\ListPlatforms;
use App\Models\Venue;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingPlatformsResource extends Resource
{
    protected static ?string $model = Venue::class;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('platforms');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Venue')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('platforms.platform_type')
                    ->label('Connected Platform')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'covermanager' => 'CoverManager',
                        'restoo' => 'Restoo',
                        default => $state,
                    }),
                IconColumn::make('platforms.is_enabled')
                    ->label('Connection Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('platforms.last_synced_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('platform_type')
                    ->label('Platform Type')
                    ->options([
                        'covermanager' => 'CoverManager',
                        'restoo' => 'Restoo',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->whereHas('platforms', function ($query) use ($data) {
                                $query->where('platform_type', $data['value']);
                            });
                        }
                    }),
                SelectFilter::make('is_enabled')
                    ->label('Connection Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            $query->whereHas('platforms', function ($query) use ($data) {
                                $query->where('is_enabled', $data['value']);
                            });
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Edit Connection'),
                Tables\Actions\Action::make('syncCoverManager')
                    ->label('Sync with Platform')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Venue $record) {
                        try {
                            // For backward compatibility, we'll use the old method for now
                            $result = $record->syncCoverManagerAvailability();

                            if ($result) {
                                Notification::make()
                                    ->title('Sync complete')
                                    ->body('Successfully synced with platform')
                                    ->success()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Sync failed')
                                ->body('Check logs for details')
                                ->danger()
                                ->send();
                        } catch (Throwable $e) {
                            Log::error('Manual platform sync failed', [
                                'error' => $e->getMessage(),
                                'venue_id' => $record->id,
                            ]);

                            Notification::make()
                                ->title('Sync failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (Venue $record) {
                        return $record->hasPlatform('covermanager');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatforms::route('/'),
            'create' => CreatePlatform::route('/create'),
            'edit' => EditPlatform::route('/{record}/edit'),
        ];
    }
}
