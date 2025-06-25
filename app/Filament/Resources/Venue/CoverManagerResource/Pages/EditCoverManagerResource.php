<?php

namespace App\Filament\Resources\Venue\CoverManagerResource\Pages;

use App\Filament\Resources\Venue\CoverManagerResource;
use App\Services\CoverManagerService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Throwable;

class EditCoverManagerResource extends EditRecord
{
    protected static string $resource = CoverManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test Connection')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    try {
                        $service = app(CoverManagerService::class);
                        $result = $service->getRestaurantData($this->record->covermanager_id);

                        if (blank($result)) {
                            Notification::make()
                                ->title('Connection Test Failed')
                                ->body('Restaurant not found in CoverManager')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Connection Test Successful')
                            ->body('Successfully connected to CoverManager restaurant')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Log::error('CoverManager test connection failed', [
                            'error' => $e->getMessage(),
                            'venue_id' => $this->record->id,
                        ]);

                        Notification::make()
                            ->title('Connection Test Failed')
                            ->body('Failed to connect: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->usesCoverManager()),
            Action::make('syncNow')
                ->label('Sync Now')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    try {
                        if ($this->record->syncCoverManagerAvailability()) {
                            Notification::make()
                                ->title('Sync Successful')
                                ->body('Successfully synced availability with CoverManager')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body('Failed to sync availability. Check logs for details.')
                                ->danger()
                                ->send();
                        }
                    } catch (Throwable $e) {
                        Log::error('Manual CoverManager sync failed', [
                            'error' => $e->getMessage(),
                            'venue_id' => $this->record->id,
                        ]);

                        Notification::make()
                            ->title('Sync Failed')
                            ->body('Failed to sync: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->usesCoverManager()),
            DeleteAction::make(),
        ];
    }
}
