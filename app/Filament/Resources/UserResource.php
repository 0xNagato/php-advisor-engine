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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        return auth()->user()->hasActiveRole('super_admin');
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
                            'venue_manager',
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
                    ->searchable(['first_name', 'last_name', 'phone']),
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
                            'venue_manager' => 'orange',
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
            ->filters([
                Filter::make('role')
                    ->form([
                        Select::make('role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'partner' => 'Partner',
                                'concierge' => 'Concierge',
                                'venue' => 'Venue',
                                'venue_manager' => 'Venue Manager',
                            ])
                            ->placeholder('All Roles')
                            ->label('Role'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['role'],
                        fn (Builder $query, string $role): Builder => $query->whereHas(
                            'roleProfiles',
                            fn (Builder $query) => $query->whereHas(
                                'role',
                                fn (Builder $query) => $query->where('name', $role)
                            )
                        )
                    )),
            ])
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
