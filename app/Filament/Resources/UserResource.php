<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use STS\FilamentImpersonate\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 19;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('roles')
                    ->multiple()
                    ->relationship(
                        'roles',
                        'name',
                        fn ($query) => $query->whereIn('name', [
                            'super_admin',
                            'partner',
                            'concierge',
                            'venue',
                        ])
                    )
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                static::getModel()::query()
                    ->with(['roleProfiles.role'])
            )
            ->recordUrl(fn (User $record): string => EditUser::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roleProfiles')
                    ->label('Roles')
                    ->getStateUsing(fn (User $record) => $record->roleProfiles->map(function ($profile) {
                        $color = match ($profile->role->name) {
                            'super_admin' => 'red',
                            'concierge' => 'blue',
                            'venue' => 'green',
                            'partner' => 'yellow',
                            default => 'gray'
                        };

                        $name = Str::title(str_replace('_', ' ', $profile->role->name));
                        $activeMarker = $profile->is_active ? ' ✓' : '';
                        $opacity = $profile->is_active ? '' : 'opacity-50';

                        return "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-$color-100 text-$color-800 $opacity'>$name$activeMarker</span>";
                    })->join(' '))
                    ->html(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Impersonate::make()
                    ->redirectTo(config('app.platform_url')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
