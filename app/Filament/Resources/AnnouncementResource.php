<?php

namespace App\Filament\Resources;

use App\Events\AnnouncementCreated;
use App\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\Message;
use App\Models\Region;
use App\Models\User;
use App\Services\PrimaShortUrls;
use AshAllenDesign\ShortURL\Models\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
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
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
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
                Section::make('New Announcement')
                    ->icon('heroicon-o-newspaper')
                    ->schema([
                        TextInput::make('title'),
                        MarkdownEditor::make('message')->required(),
                        TextInput::make('call_to_action_title'),
                        TextInput::make('call_to_action_url'),
                        Select::make('region')
                            ->label('Region')
                            ->multiple()
                            ->options(Region::all()->pluck('name', 'id')),
                        Select::make('recipient_roles')
                            ->label('Recipient Roles')
                            ->options($roles)
                            ->required()
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
                TextColumn::make('published_at')
                    ->dateTime('M jS, Y g:ia')
                    ->timezone(fn () => auth()->user()->timezone ?? config('app.timezone'))
                    ->sortable()
                    ->placeholder('Not Published')
                    ->description(fn ($record) => $record->published_at ?
                        Carbon::parse($record->published_at)
                            ->timezone(auth()->user()->timezone ?? config('app.timezone'))
                            ->diffForHumans() : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('copyLink')
                    ->label('Copy Link')
                    ->icon('heroicon-o-link')
                    ->tooltip('Copy shareable link to clipboard')
                    ->color('gray')
                    ->visible(fn (Announcement $announcement) => $announcement->published_at !== null)
                    ->modalHeading('Share Announcement Link')
                    ->modalDescription('Copy this link to share the announcement with partners and concierges.')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(function (Announcement $announcement) {
                        // Find or create a message for this announcement
                        $message = Message::query()->firstOrCreate([
                            'announcement_id' => $announcement->id,
                            'user_id' => auth()->id(),
                        ]);

                        // Generate short URL
                        $shortUrl = PrimaShortUrls::getMessageUrl($message->id);

                        // Get the short URL record to find visits data
                        $destinationUrl = route('public.announcement', ['message' => $message->id]);
                        $shortUrlRecord = ShortURL::query()->where('destination_url', $destinationUrl)->first();

                        $visitsPerDay = collect();

                        if ($shortUrlRecord) {
                            // Get visits grouped by day
                            $visitsPerDay = ShortURLVisit::query()->where('short_url_id', $shortUrlRecord->id)
                                ->selectRaw(
                                    'DATE(visited_at) as visit_date, COUNT(DISTINCT ip_address) as unique_visits'
                                )
                                ->groupBy('visit_date')
                                ->orderBy('visit_date', 'desc')
                                ->get()
                                ->map(function ($item) {
                                    $date = \Carbon\Carbon::parse($item->visit_date);

                                    return [
                                        'date' => $date->format('D M j, y'),
                                        'unique_visits' => $item->unique_visits,
                                    ];
                                });

                            // Get total unique visits
                            $totalUniqueVisits = ShortURLVisit::query()->where('short_url_id', $shortUrlRecord->id)
                                ->distinct('ip_address')
                                ->count('ip_address');
                        } else {
                            $totalUniqueVisits = 0;
                        }

                        // Return a view with copy functionality
                        return view('filament.resources.announcement-resource.pages.copy-link-modal', [
                            'shortUrl' => $shortUrl,
                            'visitsPerDay' => $visitsPerDay,
                            'totalUniqueVisits' => $totalUniqueVisits,
                        ]);
                    }),
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
