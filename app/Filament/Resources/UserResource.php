<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
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
                    ->orderByRaw('COALESCE(last_login_at, "1000-01-01") DESC')
            )
            ->recordUrl(fn (User $record): string => EditUser::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->sortable(['first_name', 'last_name'])
                    ->size('sm')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function ($query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    }))
                    ->formatStateUsing(fn (User $record): string => new HtmlString(<<<HTML
                        <div class="space-y-0.5">
                            <div class="text-xs font-semibold">{$record->name}</div>
                            <div class="text-xs text-gray-500">
                                {$record->phone}
                            </div>
                            <div class="text-xs text-gray-500">
                                {$record->email}
                            </div>
                        </div>
                    HTML))
                    ->html(),
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
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->sortable()
                    ->grow(false)
                    ->tooltip(function ($state, User $record) {
                        if ($record->last_login_at) {
                            return Carbon::parse($record->last_login_at)
                                ->setTimezone(auth()->user()->timezone ?? config('app.timezone'))
                                ->format('M j Y, g:ia');
                        }

                        return 'Never logged in';
                    })
                    ->size('xs')
                    ->formatStateUsing(function ($state, User $record) {
                        if ($record->last_login_at) {
                            return Carbon::parse($record->last_login_at)
                                ->setTimezone(auth()->user()->timezone ?? config('app.timezone'))
                                ->diffForHumans();
                        }

                        return 'Never';
                    })
                    ->default('Never'),
                TextColumn::make('created_at')
                    ->dateTime('M j Y, g:ia')
                    ->timezone(fn () => auth()->user()->timezone ?? config('app.timezone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('xs')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j Y, g:ia')
                    ->timezone(fn () => auth()->user()->timezone ?? config('app.timezone'))
                    ->visibleFrom('sm')
                    ->size('xs')
                    ->sortable(),
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
