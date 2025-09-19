<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskWhitelistResource\Pages;
use App\Filament\Resources\RiskWhitelistResource\RelationManagers;
use App\Models\RiskWhitelist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RiskWhitelistResource extends Resource
{
    protected static ?string $model = RiskWhitelist::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Risk Whitelist';

    protected static ?string $navigationGroup = 'Risk Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
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

                Forms\Components\TextInput::make('value')
                    ->label('Value')
                    ->required()
                    ->helperText(fn ($get) => match($get('type')) {
                        RiskWhitelist::TYPE_EMAIL => 'Enter full email address (e.g., trusted@example.com)',
                        RiskWhitelist::TYPE_DOMAIN => 'Enter domain only (e.g., marriott.com)',
                        RiskWhitelist::TYPE_PHONE => 'Enter phone with country code (e.g., +1234567890)',
                        RiskWhitelist::TYPE_IP => 'Enter IP or CIDR range (e.g., 192.168.1.1 or 192.168.0.0/16)',
                        RiskWhitelist::TYPE_NAME => 'Enter name pattern (case insensitive)',
                        default => 'Enter the value to whitelist'
                    }),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Additional notes about why this is whitelisted'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive entries will not be checked during risk scoring'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => RiskWhitelist::TYPE_EMAIL,
                        'success' => RiskWhitelist::TYPE_DOMAIN,
                        'warning' => RiskWhitelist::TYPE_PHONE,
                        'danger' => RiskWhitelist::TYPE_IP,
                        'secondary' => RiskWhitelist::TYPE_NAME,
                    ]),
                Tables\Columns\TextColumn::make('value')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->default('System'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRiskWhitelists::route('/'),
            'create' => Pages\CreateRiskWhitelist::route('/create'),
            'edit' => Pages\EditRiskWhitelist::route('/{record}/edit'),
        ];
    }
}
