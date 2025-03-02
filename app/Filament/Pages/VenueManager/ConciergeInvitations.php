<?php

namespace App\Filament\Pages\VenueManager;

use App\Models\Referral;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use App\Notifications\Concierge\NotifyConciergeReferral;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class ConciergeInvitations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.venue-manager.concierge-invitations';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Concierge Invitations';

    protected static string $routePath = 'venue-manager/concierge-invitations';

    public ?VenueGroup $venueGroup = null;

    /** @var Collection<int, Venue> */
    public Collection $venues;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('venue_manager');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole('venue_manager'), 403, 'You are not authorized to access this page');

        $this->venueGroup = auth()->user()->currentVenueGroup();

        /** @var User $user */
        $user = auth()->user();
        $allowedVenueIds = $this->venueGroup?->getAllowedVenueIds($user) ?? [];

        $this->venues = $this->venueGroup?->venues()
            ->when(
                filled($allowedVenueIds),
                fn ($query) => $query->whereIn('id', $allowedVenueIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->get() ?? collect();

        $this->form->fill([]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invite a Concierge')
                    ->description('Invite a concierge to book only at specific venues in your venue group')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make()
                            ->schema([
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                PhoneInput::make('phone')
                                    ->required()
                                    ->onlyCountries(config('app.countries'))
                                    ->displayNumberFormat(PhoneInputNumberType::E164)
                                    ->disallowDropdown()
                                    ->validateFor(
                                        country: config('app.countries'),
                                    )
                                    ->unique('users', 'phone'),
                            ]),
                        TextInput::make('hotel_name')
                            ->label('Hotel/Company Name')
                            ->maxLength(255),
                        CheckboxList::make('allowed_venue_ids')
                            ->label('Allowed Venues')
                            ->options($this->venues->pluck('name', 'id')->toArray())
                            ->required()
                            ->columns(2)
                            ->descriptions(
                                $this->venues->pluck('address', 'id')->toArray()
                            ),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Referral::query()
                    ->where('type', 'concierge')
                    ->where('referrer_type', 'venue_manager')
                    ->whereHas('referrer', function (Builder $query) {
                        $query->where('id', auth()->id());
                    })
                    ->when(
                        $this->venueGroup,
                        function (Builder $query) {
                            $query->whereJsonContains('meta->venue_group_id', $this->venueGroup->id);
                        }
                    )
                    ->with(['user', 'user.concierge'])
            )
            ->heading('Concierge Invitations')
            ->description('Manage concierge invitations and active concierges')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label('Hotel/Company')
                    ->formatStateUsing(fn (Referral $record) => $record->user?->concierge?->hotel_name ?? $record->company_name)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Invited At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Accepted' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('meta')
                    ->label('Allowed Venues')
                    ->formatStateUsing(function (Referral $record) {
                        if (! $record->user?->concierge) {
                            return 'Pending';
                        }

                        $concierge = $record->user->concierge;

                        $venueNames = Venue::query()
                            ->whereIn('id', $concierge->allowed_venue_ids)
                            ->pluck('name')
                            ->implode(', ');

                        return $venueNames ?: 'None';
                    })
                    ->visible(fn () => $this->venues->isNotEmpty()),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Action for pending invitations
                Action::make('resend')
                    ->label('Resend Invitation')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (Referral $record) => $record->status === 'Pending')
                    ->action(function (Referral $record): void {
                        $record->notify(new NotifyConciergeReferral(referral: $record, channel: 'sms'));

                        if ($record->email) {
                            $record->notify(new NotifyConciergeReferral(referral: $record, channel: 'mail'));
                        }

                        $record->update(['reminded_at' => now()]);

                        Notification::make()
                            ->title('Invitation resent successfully')
                            ->success()
                            ->send();
                    }),

                // Action for active concierges
                Action::make('edit')
                    ->label('Edit Venues')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn (Referral $record) => $record->status === 'Accepted' && $record->user?->concierge)
                    ->form([
                        CheckboxList::make('allowed_venue_ids')
                            ->label('Allowed Venues')
                            ->options($this->venues->pluck('name', 'id')->toArray())
                            ->required()
                            ->columns(2)
                            ->descriptions(
                                $this->venues->pluck('address', 'id')->toArray()
                            ),
                    ])
                    ->fillForm(function (Referral $record): array {
                        // Get venue IDs from the concierge
                        $venueIds = $record->user?->concierge?->allowed_venue_ids ?? [];

                        // Cast venue IDs to strings for Filament's CheckboxList
                        $venueIdsAsStrings = array_map('strval', $venueIds);

                        return [
                            'allowed_venue_ids' => $venueIdsAsStrings,
                        ];
                    })
                    ->action(function (Referral $record, array $data): void {
                        if ($record->user?->concierge) {
                            $record->user->concierge->update([
                                'allowed_venue_ids' => array_map('intval', $data['allowed_venue_ids']),
                            ]);

                            Notification::make()
                                ->title('Concierge updated successfully')
                                ->success()
                                ->send();
                        }
                    }),

                // Delete action for pending referrals only
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->visible(fn (Referral $record) => $record->status === 'Pending')
                    ->action(function (Referral $record): void {
                        // Only delete the referral, not the concierge account
                        $record->delete();

                        Notification::make()
                            ->title('Invitation deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $referral = Referral::query()->create([
            'referrer_id' => auth()->id(),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'type' => 'concierge',
            'referrer_type' => 'venue_manager',
            'company_name' => $data['hotel_name'],
            'region_id' => auth()->user()->region ?? config('app.default_region'),
            'meta' => [
                'venue_group_id' => $this->venueGroup->id,
                'allowed_venue_ids' => array_map('intval', $data['allowed_venue_ids']),
            ],
        ]);

        $referral->notify(new NotifyConciergeReferral(referral: $referral, channel: 'sms'));

        if ($data['email'] ?? null) {
            $referral->notify(new NotifyConciergeReferral(referral: $referral, channel: 'mail'));
        }

        Notification::make()
            ->title('Concierge invitation sent successfully')
            ->success()
            ->send();

        $this->form->fill();
    }
}
