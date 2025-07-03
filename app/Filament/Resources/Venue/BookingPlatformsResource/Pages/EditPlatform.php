<?php

namespace App\Filament\Resources\Venue\BookingPlatformsResource\Pages;

use App\Filament\Resources\Venue\BookingPlatformsResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPlatform extends EditRecord
{
    protected static string $resource = BookingPlatformsResource::class;

    public ?array $data = [];

    public function mount($record): void
    {
        parent::mount($record);

        // Since record is now a VenuePlatform, we can use it directly
        $this->form->fill([
            'venue_id' => $this->record->venue_id,
            'platform_type' => $this->record->platform_type,
            'configuration' => $this->record->configuration,
            'is_enabled' => $this->record->is_enabled,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Venue Information')
                    ->schema([
                        Select::make('venue_id')
                            ->label('Venue')
                            ->relationship('venue', 'name')
                            ->disabled() // Can't change venue when editing
                            ->required(),
                    ]),

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
                    ]),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_enabled')
                            ->label('Enable Platform')
                            ->helperText('Enable or disable this platform connection'),
                    ]),
            ])
            ->statePath('data');
    }

    public function getHeading(): string
    {
        return "Edit {$this->record->venue->name} - {$this->record->platform_type} Connection";
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
                ->modalDescription('Are you sure you want to disconnect this platform? This will remove the platform connection.')
                ->modalSubmitActionLabel('Disconnect')
                ->action(function () {
                    // Delete the platform connection record
                    $this->record->delete();

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
        // Since record is already a VenuePlatform, update it directly
        $record->update([
            'is_enabled' => $data['is_enabled'] ?? true,
            'configuration' => $data['configuration'] ?? [],
        ]);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
