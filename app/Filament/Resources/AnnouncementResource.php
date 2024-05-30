<?php

namespace App\Filament\Resources;

use App\Events\AnnouncementCreated;
use App\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\Region;
use App\Models\User;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
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
                        TextInput::make('title'),
                        MarkdownEditor::make('message'),
                        TextInput::make('call_to_action_title'),
                        TextInput::make('call_to_action_url'),
                        Select::make('region')
                            ->label('Region')
                            ->options(Region::all()->pluck('name', 'id')),
                        Select::make('recipient_roles')
                            ->label('Recipient Roles')
                            ->options($roles)
                            ->multiple(),
                        Select::make('recipient_user_ids')
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
                EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
