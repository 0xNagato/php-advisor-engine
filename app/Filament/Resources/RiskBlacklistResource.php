<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskBlacklistResource\Pages;
use App\Filament\Resources\RiskBlacklistResource\RelationManagers;
use App\Models\RiskBlacklist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RiskBlacklistResource extends Resource
{
    protected static ?string $model = RiskBlacklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationLabel = 'Risk Blacklist';

    protected static ?string $navigationGroup = 'Risk Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        RiskBlacklist::TYPE_EMAIL => 'Email',
                        RiskBlacklist::TYPE_DOMAIN => 'Domain',
                        RiskBlacklist::TYPE_PHONE => 'Phone',
                        RiskBlacklist::TYPE_IP => 'IP Address',
                        RiskBlacklist::TYPE_NAME => 'Name',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('value')
                    ->label('Value')
                    ->required()
                    ->helperText(fn ($get) => match($get('type')) {
                        RiskBlacklist::TYPE_EMAIL => 'Enter full email address (e.g., spam@example.com)',
                        RiskBlacklist::TYPE_DOMAIN => 'Enter domain only (e.g., example.com)',
                        RiskBlacklist::TYPE_PHONE => 'Enter phone with country code (e.g., +1234567890)',
                        RiskBlacklist::TYPE_IP => 'Enter IP or CIDR range (e.g., 192.168.1.1 or 192.168.0.0/16)',
                        RiskBlacklist::TYPE_NAME => 'Enter name pattern (case insensitive)',
                        default => 'Enter the value to blacklist'
                    }),

                Forms\Components\TextInput::make('reason')
                    ->label('Reason')
                    ->required()
                    ->helperText('Why is this being blacklisted?'),

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
                        'primary' => RiskBlacklist::TYPE_EMAIL,
                        'success' => RiskBlacklist::TYPE_DOMAIN,
                        'warning' => RiskBlacklist::TYPE_PHONE,
                        'danger' => RiskBlacklist::TYPE_IP,
                        'secondary' => RiskBlacklist::TYPE_NAME,
                    ]),
                Tables\Columns\TextColumn::make('value')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('reason')
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
            'index' => Pages\ListRiskBlacklists::route('/'),
            'create' => Pages\CreateRiskBlacklist::route('/create'),
            'edit' => Pages\EditRiskBlacklist::route('/{record}/edit'),
        ];
    }
}
