<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskWhitelistResource\Pages\CreateRiskWhitelist;
use App\Filament\Resources\RiskWhitelistResource\Pages\EditRiskWhitelist;
use App\Filament\Resources\RiskWhitelistResource\Pages\ListRiskWhitelists;
use App\Models\RiskWhitelist;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RiskWhitelistResource extends Resource
{
    protected static ?string $model = RiskWhitelist::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Risk Whitelist';

    protected static ?string $navigationGroup = 'Risk Management';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('Type')
                    ->options([
                        RiskWhitelist::TYPE_EMAIL => 'Email',
                        RiskWhitelist::TYPE_DOMAIN => 'Domain',
                        RiskWhitelist::TYPE_PHONE => 'Phone',
                        RiskWhitelist::TYPE_IP => 'IP Address',
                        RiskWhitelist::TYPE_NAME => 'Name',
                    ])
                    ->required()
                    ->reactive(),

                TextInput::make('value')
                    ->label('Value')
                    ->required()
                    ->helperText(fn ($get) => match ($get('type')) {
                        RiskWhitelist::TYPE_EMAIL => 'Enter full email address (e.g., trusted@example.com)',
                        RiskWhitelist::TYPE_DOMAIN => 'Enter domain only (e.g., marriott.com)',
                        RiskWhitelist::TYPE_PHONE => 'Enter phone with country code (e.g., +1234567890)',
                        RiskWhitelist::TYPE_IP => 'Enter IP or CIDR range (e.g., 192.168.1.1 or 192.168.0.0/16)',
                        RiskWhitelist::TYPE_NAME => 'Enter name pattern (case insensitive)',
                        default => 'Enter the value to whitelist'
                    }),

                Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Additional notes about why this is whitelisted'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive entries will not be checked during risk scoring'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('type')
                    ->colors([
                        'primary' => RiskWhitelist::TYPE_EMAIL,
                        'success' => RiskWhitelist::TYPE_DOMAIN,
                        'warning' => RiskWhitelist::TYPE_PHONE,
                        'danger' => RiskWhitelist::TYPE_IP,
                        'secondary' => RiskWhitelist::TYPE_NAME,
                    ]),
                TextColumn::make('value')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('notes')
                    ->limit(50),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->default('System'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListRiskWhitelists::route('/'),
            'create' => CreateRiskWhitelist::route('/create'),
            'edit' => EditRiskWhitelist::route('/{record}/edit'),
        ];
    }
}
