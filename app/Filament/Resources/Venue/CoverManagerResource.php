<?php

namespace App\Filament\Resources\Venue;

use App\Filament\Resources\Venue\CoverManagerResource\Pages\EditCoverManagerResource;
use App\Filament\Resources\Venue\CoverManagerResource\Pages\ListCoverManagerResources;
use App\Models\Venue;
use App\Services\CoverManagerService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CoverManagerResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'CoverManager';

    protected static ?string $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'integrations/covermanager';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('uses_covermanager', true);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('CoverManager Integration')
                    ->schema([
                        Toggle::make('uses_covermanager')
                            ->label('Enable CoverManager')
                            ->required(),
                        TextInput::make('covermanager_id')
                            ->label('CoverManager Restaurant ID')
                            ->helperText('The ID of the restaurant in CoverManager system')
                            ->required()
                            ->visible(fn (Get $get) => $get('uses_covermanager')),
                        Toggle::make('covermanager_sync_enabled')
                            ->label('Enable Automatic Sync')
                            ->helperText('Enable automatic synchronization of availability with CoverManager')
                            ->visible(fn (Get $get) => $get('uses_covermanager')),
                        Actions::make([
                            Action::make('testConnection')
                                ->label('Test Connection')
                                ->color('warning')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function (Venue $record) {
                                    try {
                                        $service = app(CoverManagerService::class);
                                        $result = $service->getRestaurantData($record->covermanager_id);

                                        if (blank($result)) {
                                            return Action::danger('Connection failed: Restaurant not found');
                                        }

                                        return Action::success();
                                    } catch (Throwable $e) {
                                        Log::error('CoverManager test connection failed', [
                                            'error' => $e->getMessage(),
                                            'venue_id' => $record->id,
                                        ]);

                                        return Action::danger('Connection failed: '.$e->getMessage());
                                    }
                                })
                                ->visible(fn (Get $get) => $get('uses_covermanager')),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('uses_covermanager')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('covermanager_id')
                    ->label('Restaurant ID')
                    ->searchable(),
                IconColumn::make('covermanager_sync_enabled')
                    ->label('Auto Sync')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('last_covermanager_sync')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sync_status')
                    ->options([
                        'enabled' => 'Sync Enabled',
                        'disabled' => 'Sync Disabled',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'enabled') {
                            $query->where('covermanager_sync_enabled', true);
                        } elseif ($data['value'] === 'disabled') {
                            $query->where('covermanager_sync_enabled', false);
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                Tables\Actions\Action::make('sync')
                    ->label('Sync Now')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Venue $record) {
                        try {
                            $result = $record->syncCoverManagerAvailability();

                            if ($result) {
                                return Action::success();
                            }

                            return Action::danger('Sync failed. Check logs for details.');
                        } catch (Throwable $e) {
                            Log::error('Manual CoverManager sync failed', [
                                'error' => $e->getMessage(),
                                'venue_id' => $record->id,
                            ]);

                            return Action::danger('Sync failed: '.$e->getMessage());
                        }
                    })
                    ->visible(fn (Venue $record) => $record->uses_covermanager),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('syncSelected')
                        ->label('Sync Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Collection $records) {
                            $success = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    if ($record->syncCoverManagerAvailability()) {
                                        $success++;
                                    } else {
                                        $failed++;
                                    }
                                } catch (Throwable $e) {
                                    Log::error('Bulk CoverManager sync failed', [
                                        'error' => $e->getMessage(),
                                        'venue_id' => $record->id,
                                    ]);

                                    $failed++;
                                }
                            }

                            if ($failed === 0) {
                                return Action::success();
                            }

                            return Action::danger("Sync completed with issues: {$success} succeeded, {$failed} failed.");
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoverManagerResources::route('/'),
            'edit' => EditCoverManagerResource::route('/{record}/edit'),
        ];
    }
}
