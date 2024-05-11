<?php

namespace App\Filament\Resources;

use App\Events\AnnouncementCreated;
use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Models\Region;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        $roles = Role::all()
            ->whereNotIn('name', ['panel_user', 'super_admin'])
            ->pluck('name', 'id');

        $recipients = User::role($roles)
            ->get()
            ->mapWithKeys(fn (User $user, int $key) => [$user['id'] => $user['first_name'].' '.$user['last_name']]);

        return $form
            ->schema([
                Section::make('New Annoucement')
                    ->icon('heroicon-o-newspaper')
                    ->schema([
                        Forms\Components\TextInput::make('title'),
                        Forms\Components\MarkdownEditor::make('message'),
                        Forms\Components\TextInput::make('call_to_action_title'),
                        Forms\Components\TextInput::make('call_to_action_url'),
                        Forms\Components\Select::make('region')
                            ->label('Region')
                            ->options(Region::all()->pluck('name', 'id')),
                        Forms\Components\Select::make('recipient_roles')
                            ->label('Recipient Roles')
                            ->options($roles)
                            ->multiple(),
                        Forms\Components\Select::make('recipient_user_ids')
                            ->label('Recipient Users')
                            ->options($recipients)
                            ->multiple()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
            ])
            ->actions([
                Action::make('Publish')
                    ->icon('fas-paper-plane')
                    ->requiresConfirmation()
                    ->hidden(fn (Announcement $announcement) => $announcement->published_at !== null)
                    ->action(function (Announcement $announcement) {
                        $announcement->update(['published_at' => now()]);
                        AnnouncementCreated::dispatch($announcement);
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
