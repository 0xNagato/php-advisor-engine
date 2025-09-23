<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskBlacklistResource\Pages\CreateRiskBlacklist;
use App\Filament\Resources\RiskBlacklistResource\Pages\EditRiskBlacklist;
use App\Filament\Resources\RiskBlacklistResource\Pages\ListRiskBlacklists;
use App\Models\RiskBlacklist;
use Filament\Forms\Components\Select;
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

class RiskBlacklistResource extends Resource
{
    protected static ?string $model = RiskBlacklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationLabel = 'Risk Blacklist';

    protected static ?string $navigationGroup = 'Risk Management';

    protected static ?int $navigationSort = 3;

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
                        RiskBlacklist::TYPE_EMAIL => 'Email',
                        RiskBlacklist::TYPE_DOMAIN => 'Domain',
                        RiskBlacklist::TYPE_PHONE => 'Phone',
                        RiskBlacklist::TYPE_IP => 'IP Address',
                        RiskBlacklist::TYPE_NAME => 'Name',
                    ])
                    ->required()
                    ->reactive(),

                TextInput::make('value')
                    ->label('Value')
                    ->required()
                    ->helperText(fn ($get) => match ($get('type')) {
                        RiskBlacklist::TYPE_EMAIL => 'Enter full email address (e.g., spam@example.com)',
                        RiskBlacklist::TYPE_DOMAIN => 'Enter domain only (e.g., example.com)',
                        RiskBlacklist::TYPE_PHONE => 'Enter phone with country code (e.g., +1234567890)',
                        RiskBlacklist::TYPE_IP => 'Enter IP or CIDR range (e.g., 192.168.1.1 or 192.168.0.0/16)',
                        RiskBlacklist::TYPE_NAME => 'Enter name pattern (case insensitive)',
                        default => 'Enter the value to blacklist'
                    }),

                TextInput::make('reason')
                    ->label('Reason')
                    ->required()
                    ->helperText('Why is this being blacklisted?'),

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
                        'primary' => RiskBlacklist::TYPE_EMAIL,
                        'success' => RiskBlacklist::TYPE_DOMAIN,
                        'warning' => RiskBlacklist::TYPE_PHONE,
                        'danger' => RiskBlacklist::TYPE_IP,
                        'secondary' => RiskBlacklist::TYPE_NAME,
                    ]),
                TextColumn::make('value')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('reason')
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
            'index' => ListRiskBlacklists::route('/'),
            'create' => CreateRiskBlacklist::route('/create'),
            'edit' => EditRiskBlacklist::route('/{record}/edit'),
        ];
    }
}
