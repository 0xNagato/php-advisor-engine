<?php

namespace App\Filament\Pages\VenueManager;

use App\Models\Referral;
use App\Models\VenueGroup;
use App\Notifications\VenueManagerInvitation;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class ManageVenueManagers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Venue Managers';

    protected static string $view = 'filament.pages.venue-manager.manage-venue-managers';

    public ?VenueGroup $venueGroup = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole('venue_manager'), 403);

        $this->venueGroup = auth()->user()->currentVenueGroup();

        abort_unless(
            $this->venueGroup && $this->venueGroup->primary_manager_id === auth()->id(),
            403,
            'Only primary venue managers can access this page'
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Venue Managers')
            ->query(
                Referral::query()
                    ->where('type', 'venue_manager')
                    ->where(function ($query) {
                        $query->whereJsonContains('meta->venue_group_id', $this->venueGroup->id)
                            ->orWhere('meta->venue_group_id', $this->venueGroup->id);
                    })
            )
            ->columns([
                TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Accepted' ? 'success' : 'warning'),
            ])
            ->actions([
                Action::make('manageAccess')
                    ->icon('heroicon-m-key')
                    ->label('Manage Access')
                    ->modalHeading(fn (Referral $record): string => "Manage Access for {$record->name}")
                    ->modalDescription('Select which venues this manager can access.')
                    ->form([
                        CheckboxList::make('allowed_venues')
                            ->label('Allowed Venues')
                            ->options(fn () => $this->venueGroup->venues->pluck('name', 'id'))
                            ->columns(2)
                            ->default(fn (Referral $record): array => $record->meta['allowed_venue_ids'] ?? []),
                    ])
                    ->action(function (Referral $record, array $data): void {
                        $meta = $record->meta;
                        $meta['allowed_venue_ids'] = $data['allowed_venues'];

                        $record->update([
                            'meta' => $meta,
                        ]);

                        if ($record->user) {
                            $this->venueGroup->managers()->updateExistingPivot(
                                $record->user->id,
                                [
                                    'allowed_venue_ids' => $data['allowed_venues'],
                                    'current_venue_id' => in_array(
                                        $this->venueGroup->currentVenue($record->user)?->id,
                                        $data['allowed_venues']
                                    )
                                        ? $this->venueGroup->currentVenue($record->user)?->id
                                        : ($data['allowed_venues'][0] ?? null),
                                ]
                            );
                        }

                        Notification::make()
                            ->success()
                            ->title('Venue access updated successfully')
                            ->send();
                    })
                    ->visible(fn (Referral $record) => $record->secured_at),
                Action::make('resend')
                    ->icon('heroicon-m-envelope')
                    ->action(function (Referral $record) {
                        $record->notify(new VenueManagerInvitation($record, $this->venueGroup));
                        Notification::make()->success()->title('Invitation resent')->send();
                    })
                    ->visible(fn (Referral $record) => ! $record->secured_at),
                Action::make('delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Invitation')
                    ->modalDescription('Are you sure you want to delete this invitation? This cannot be undone.')
                    ->action(function (Referral $record) {
                        $record->delete();
                        Notification::make()
                            ->success()
                            ->title('Invitation deleted')
                            ->send();
                    })
                    ->visible(fn (Referral $record) => ! $record->secured_at),
            ])
            ->headerActions([
                Action::make('invite')
                    ->label('Invite Manager')
                    ->icon('heroicon-m-plus')
                    ->form([
                        Grid::make([
                            'default' => 2,
                            'sm' => 2,
                        ])
                            ->schema([
                                TextInput::make('first_name')
                                    ->hiddenLabel()
                                    ->placeholder('First Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->hiddenLabel()
                                    ->placeholder('Last Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->hiddenLabel()
                                    ->placeholder('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique('users', 'email')
                                    ->columnSpan(2),
                                PhoneInput::make('phone')
                                    ->hiddenLabel()
                                    ->onlyCountries(config('app.countries'))
                                    ->displayNumberFormat(PhoneInputNumberType::E164)
                                    ->disallowDropdown()
                                    ->unique('users', 'phone')
                                    ->validateFor(
                                        country: config('app.countries'),
                                        type: PhoneNumberType::MOBILE,
                                        lenient: true,
                                    )
                                    ->columnSpan(2)
                                    ->required(),
                                CheckboxList::make('allowed_venues')
                                    ->label(new HtmlString('
                                    <div class="block w-full mb-1">Venue Access</div>
                                    <div class="block w-full mb-2 font-normal text-gray-500">Select which venues this manager will have access to.</div>
                                '))
                                    ->options(fn () => $this->venueGroup->venues->pluck('name', 'id'))
                                    ->columns(2)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Please select at least one venue.',
                                    ])
                                    ->markAsRequired(false)
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $referral = Referral::query()->create([
                            'referrer_id' => auth()->id(),
                            'email' => $data['email'],
                            'phone' => $data['phone'],
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'type' => 'venue_manager',
                            'referrer_type' => 'venue_manager',
                            'meta' => [
                                'venue_group_id' => $this->venueGroup->id,
                                'allowed_venue_ids' => $data['allowed_venues'],
                            ],
                        ]);

                        $referral->notify(new VenueManagerInvitation($referral, $this->venueGroup));

                        Notification::make()
                            ->success()
                            ->title('Invitation sent successfully')
                            ->send();
                    }),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('venue_manager')
            && auth()->user()?->currentVenueGroup()?->primary_manager_id === auth()->id();
    }
}
