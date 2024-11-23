<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages\CreateAdmin;
use App\Filament\Resources\AdminResource\Pages\ListAdmins;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'admin';

    protected static ?string $navigationIcon = 'gmdi-shield-o';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::role('super_admin'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
        ];
    }
}
