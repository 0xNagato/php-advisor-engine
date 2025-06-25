<?php

namespace App\Filament\Resources\Venue\CoverManagerResource\Pages;

use App\Filament\Resources\Venue\CoverManagerResource;
use App\Services\CoverManagerService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListCoverManagerResources extends ListRecords
{
    protected static string $resource = CoverManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleEnvironment')
                ->label(fn () => 'Current: '.(config('services.covermanager.environment') === 'beta' ? 'BETA' : 'PRODUCTION'))
                ->color(config('services.covermanager.environment') === 'beta' ? 'warning' : 'success')
                ->icon('heroicon-o-cog-6-tooth')
                ->form([
                    TextInput::make('environment')
                        ->label('Environment')
                        ->placeholder('Set the CoverManager environment')
                        ->default(config('services.covermanager.environment'))
                        ->required()
                        ->datalist([
                            'beta' => 'Beta (Test)',
                            'production' => 'Production',
                        ]),
                    TextInput::make('base_url')
                        ->label('API Base URL')
                        ->placeholder('Set the CoverManager API base URL')
                        ->default(config('services.covermanager.base_url'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Using Laravel's config facade to update runtime configuration
                    config(['services.covermanager.environment' => $data['environment']]);
                    config(['services.covermanager.base_url' => $data['base_url']]);

                    // In a real application, you might want to persist this to .env
                    // but for simplicity we're just updating the config values for the current session

                    Notification::make()
                        ->title('Environment Updated')
                        ->body("Environment set to {$data['environment']} mode")
                        ->success()
                        ->send();
                }),
            Action::make('searchRestaurants')
                ->label('Search CoverManager Restaurants')
                ->icon('heroicon-o-magnifying-glass')
                ->form([
                    TextInput::make('city')
                        ->label('City')
                        ->placeholder('Enter city name to search')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = app(CoverManagerService::class);
                        $restaurants = $service->getRestaurants($data['city']);

                        if (blank($restaurants)) {
                            Notification::make()
                                ->title('No restaurants found')
                                ->body('No restaurants found for the given city')
                                ->warning()
                                ->send();

                            return;
                        }

                        // Store results in session for display in a modal
                        session(['covermanager_search_results' => $restaurants]);

                        $this->showRestaurantsModal();
                    } catch (Throwable $e) {
                        Log::error('CoverManager restaurant search failed', [
                            'error' => $e->getMessage(),
                            'city' => $data['city'],
                        ]);

                        Notification::make()
                            ->title('Search failed')
                            ->body('Failed to search restaurants: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('refreshSync')
                ->label('Refresh All')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $venues = $this->getResource()::getEloquentQuery()->get();
                    $success = 0;
                    $failed = 0;

                    foreach ($venues as $venue) {
                        try {
                            if ($venue->usesCoverManager() && $venue->covermanager_sync_enabled) {
                                if ($venue->syncCoverManagerAvailability()) {
                                    $success++;
                                } else {
                                    $failed++;
                                }
                            }
                        } catch (Throwable $e) {
                            Log::error('CoverManager auto-refresh sync failed', [
                                'error' => $e->getMessage(),
                                'venue_id' => $venue->id,
                            ]);

                            $failed++;
                        }
                    }

                    if ($failed === 0 && $success > 0) {
                        Notification::make()
                            ->title('Sync completed')
                            ->body("Successfully synced {$success} venues")
                            ->success()
                            ->send();
                    } elseif ($success > 0) {
                        Notification::make()
                            ->title('Sync completed with issues')
                            ->body("Synced {$success} venues, {$failed} failed")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync failed')
                            ->body('Failed to sync any venues')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function showRestaurantsModal(): void
    {
        $restaurants = session('covermanager_search_results', []);

        if (blank($restaurants)) {
            return;
        }

        $this->dispatch('open-modal', id: 'covermanager-restaurants');
    }
}
