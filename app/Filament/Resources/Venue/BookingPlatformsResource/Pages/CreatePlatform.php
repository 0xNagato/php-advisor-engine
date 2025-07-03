<?php

namespace App\Filament\Resources\Venue\BookingPlatformsResource\Pages;

use App\Filament\Resources\Venue\BookingPlatformsResource;
use App\Models\Venue;
use App\Models\VenuePlatform;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePlatform extends CreateRecord
{
    protected static string $resource = BookingPlatformsResource::class;

    protected static ?string $title = 'Connect Platform';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'platform_type' => '',
            'is_enabled' => true,
            'configuration' => [
                'api_key' => '',
                'account' => '',
                'restaurant_id' => '',
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Select Venue')
                    ->schema([
                        Select::make('venue_id')
                            ->label('Venue')
                            ->options(fn () => Venue::query()
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
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
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('configuration', []);
                            }),
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

                Toggle::make('is_enabled')
                    ->label('Enable Platform')
                    ->helperText('Whether this booking platform is active')
                    ->default(true),
            ])
            ->statePath('data');
    }

    public function getHeading(): string
    {
        return 'Connect Platform to Venue';
    }

    public function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Connect Platform'),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Get the venue by ID
        $venue = Venue::query()->findOrFail($data['venue_id']);

        // Create the platform connection
        $platform = new VenuePlatform;
        $platform->venue_id = $venue->id;
        $platform->platform_type = $data['platform_type'];
        $platform->is_enabled = $data['is_enabled'] ?? true;
        $platform->configuration = $data['configuration'];
        $platform->save();

        return $venue;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
