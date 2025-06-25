<?php

namespace App\Filament\Resources\Venue\BookingPlatformsResource\Pages;

use App\Filament\Resources\Venue\BookingPlatformsResource;
use App\Models\VenuePlatform;
use App\Services\CoverManagerService;
use App\Services\RestooService;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class EditPlatform extends EditRecord
{
    protected static string $resource = BookingPlatformsResource::class;

    public ?array $data = [];

    public function mount($record): void
    {
        parent::mount($record);

        // Find the current venue platform
        $platform = VenuePlatform::query()
            ->where('venue_id', $this->record->id)
            ->first();

        if ($platform) {
            $this->form->fill([
                'platform_type' => $platform->platform_type,
                'configuration' => $platform->configuration,
                'is_enabled' => $platform->is_enabled,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Platform Selection')
                    ->schema([
                        Select::make('platform_type')
                            ->label('Platform Type')
                            ->options([
                                'covermanager' => 'CoverManager',
                                'restoo' => 'Restoo',
                            ])
                            ->required()
                            ->disabled() // Can't change platform type when editing
                            ->reactive(),
                    ]),

                Section::make('Platform Configuration')
                    ->schema([
                        // CoverManager Configuration
                        TextInput::make('configuration.restaurant_id')
                            ->label('Restaurant ID')
                            ->helperText('The ID of the restaurant in CoverManager system')
                            ->required()
                            ->visible(fn (callable $get) => $get('platform_type') === 'covermanager'),

                        \Filament\Forms\Components\Actions::make([
                            FormAction::make('testCoverManagerConnection')
                                ->label('Test Connection')
                                ->color('warning')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function () {
                                    try {
                                        $restaurantId = $this->data['configuration']['restaurant_id'] ?? null;

                                        Log::debug('CoverManager test connection attempt', [
                                            'restaurantId' => $restaurantId,
                                            'form_data' => $this->data,
                                        ]);

                                        if (empty($restaurantId)) {
                                            Notification::make()
                                                ->title('Connection failed')
                                                ->body('Restaurant ID is required')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $service = app(CoverManagerService::class);
                                        $result = $service->testRestaurantId($restaurantId);

                                        if (! $result) {
                                            Notification::make()
                                                ->title('Connection failed')
                                                ->body('Restaurant not found')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        Notification::make()
                                            ->title('Connection successful')
                                            ->success()
                                            ->send();
                                    } catch (Throwable $e) {
                                        Log::error('CoverManager test connection failed', [
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString(),
                                        ]);

                                        Notification::make()
                                            ->title('Connection failed')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn (callable $get) => $get('platform_type') === 'covermanager'),
                        ])->visible(fn (callable $get) => $get('platform_type') === 'covermanager'),

                        // Restoo Configuration
                        TextInput::make('configuration.api_key')
                            ->label('API Key')
                            ->helperText('The API key provided by Restoo')
                            ->required()
                            ->visible(fn (callable $get) => $get('platform_type') === 'restoo'),

                        TextInput::make('configuration.account')
                            ->label('Account Name')
                            ->helperText('The Restoo account name')
                            ->required()
                            ->visible(fn (callable $get) => $get('platform_type') === 'restoo'),

                        \Filament\Forms\Components\Actions::make([
                            FormAction::make('testRestooConnection')
                                ->label('Test Connection')
                                ->color('warning')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function () {
                                    try {
                                        $apiKey = $this->data['configuration']['api_key'] ?? null;
                                        $account = $this->data['configuration']['account'] ?? null;

                                        Log::debug('Restoo test connection attempt', [
                                            'apiKey' => $apiKey,
                                            'account' => $account,
                                            'form_data' => $this->data,
                                        ]);

                                        if (empty($apiKey) || empty($account)) {
                                            Notification::make()
                                                ->title('Connection failed')
                                                ->body('API key and account name are required')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $service = app(RestooService::class);
                                        $result = $service->testCredentials($apiKey, $account);

                                        if (! $result) {
                                            Notification::make()
                                                ->title('Connection failed')
                                                ->body('Invalid credentials')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        Notification::make()
                                            ->title('Connection successful')
                                            ->success()
                                            ->send();
                                    } catch (Throwable $e) {
                                        Log::error('Restoo test connection failed', [
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString(),
                                        ]);

                                        Notification::make()
                                            ->title('Connection failed')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn (callable $get) => $get('platform_type') === 'restoo'),
                        ])->visible(fn (callable $get) => $get('platform_type') === 'restoo'),
                    ]),

                Toggle::make('is_enabled')
                    ->label('Enable Platform')
                    ->helperText('Whether this booking platform is active')
                    ->default(true),
            ])
            ->statePath('data');
    }

    public function getHeading(): string
    {
        return "Edit {$this->record->name}'s Platform Connection";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disconnect')
                ->label('Disconnect Platform')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->requiresConfirmation()
                ->modalHeading('Disconnect Platform')
                ->modalDescription('Are you sure you want to disconnect this platform? This will remove the platform connection but will not delete the venue.')
                ->modalSubmitActionLabel('Disconnect')
                ->action(function () {
                    // Delete only the platform connection, not the venue
                    VenuePlatform::query()
                        ->where('venue_id', $this->record->id)
                        ->delete();

                    Notification::make()
                        ->title('Platform disconnected')
                        ->body('The platform connection has been removed successfully.')
                        ->success()
                        ->send();

                    return redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // First get or create the platform record
        $platform = VenuePlatform::query()
            ->where('venue_id', $record->id)
            ->where('platform_type', $data['platform_type'])
            ->first();

        if (! $platform) {
            $platform = new VenuePlatform;
            $platform->venue_id = $record->id;
            $platform->platform_type = $data['platform_type'];
        }

        // Update platform data
        $platform->is_enabled = $data['is_enabled'] ?? true;
        $platform->configuration = $data['configuration'];
        $platform->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
