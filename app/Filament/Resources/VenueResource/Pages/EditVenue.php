<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Enums\VenueStatus;
use App\Filament\Resources\VenueResource;
use App\Filament\Resources\VenueResource\Components\VenueContactsForm;
use App\Models\Partner;
use App\Models\Region;
use App\Models\Venue;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Contracts\Support\Htmlable;
use libphonenumber\PhoneNumberType;
use RuntimeException;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * Class EditVenue
 *
 * @method Venue getRecord()
 */
class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Venue Information')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        FileUpload::make('venue_logo_path')
                            ->label('Venue Logo')
                            ->disk('do')
                            ->directory('venues')
                            ->visibility('public'),
                        TextInput::make('name')
                            ->label('Venue Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('region')
                            ->placeholder('Select Region')
                            ->options(Region::all()->sortBy('id')->pluck('name', 'id'))
                            ->required()
                            ->afterStateUpdated(function ($state, Venue $record) {
                                $region = Region::query()->find($state);
                                if ($region) {
                                    $record->timezone = $region->timezone;
                                    $record->save();
                                }
                            }),
                        TextInput::make('primary_contact_name')
                            ->label('Primary Contact Name')
                            ->required(),
                        PhoneInput::make('contact_phone')
                            ->label('Primary Contact Phone')
                            ->onlyCountries(config('app.countries'))
                            ->displayNumberFormat(PhoneInputNumberType::E164)
                            ->disallowDropdown()
                            ->validateFor(
                                country: config('app.countries'),
                                type: PhoneNumberType::MOBILE,
                                lenient: true,
                            )
                            ->required()
                            ->initialCountry('US'),
                        TextInput::make('daily_prime_bookings_cap')
                            ->label('Daily Prime Bookings Cap')
                            ->helperText('Maximum number of prime-time bookings allowed per day. Leave empty for no limit.')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(999),
                        TextInput::make('daily_non_prime_bookings_cap')
                            ->label('Daily Non-Prime Bookings Cap')
                            ->helperText('Maximum number of non-prime-time bookings allowed per day. Leave empty for no limit.')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(999),
                    ]),
                Repeater::make('contacts')
                    ->columnSpanFull()
                    ->addActionLabel('Add Contact')
                    ->label('Contacts')
                    ->schema(
                        VenueContactsForm::schema()
                    ),

                Section::make('Payout Information')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        TextInput::make('booking_fee')
                            ->label('Booking Fee')
                            ->prefix('$')
                            ->default(200)
                            ->numeric()
                            ->required(),
                        TextInput::make('payout_venue')
                            ->label('Payout Venue')
                            ->default(60)
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('minimum_spend')
                            ->label('Minimum Spend')
                            ->prefix('$')
                            ->numeric(),
                    ]),
                Section::make('Special Pricing')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Repeater::make('special_pricing')
                            ->hiddenLabel()
                            ->relationship('specialPricing')
                            ->addActionLabel('Add Day')
                            ->label('Special Pricing')
                            ->schema([
                                TextInput::make('date')
                                    ->label('Date')
                                    ->type('date')
                                    ->required(),
                                TextInput::make('fee')
                                    ->label('Fee')
                                    ->prefix('$')
                                    ->numeric()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $currentPartnerName = $this->getRecord()->partnerReferral ? $this->getRecord()->partnerReferral->user->name : 'No Partner';

        return [
            Action::make('Change Partner')
                ->form([
                    Select::make('new_partner_id')
                        ->label('New Partner')
                        ->options(Partner::with('user')->get()->pluck('user.name', 'id'))
                        ->default($this->getRecord()->user->partner_referral_id)
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $this->getRecord()->updateReferringPartner($data['new_partner_id']);
                        Notification::make()
                            ->title('Partner Changed Successfully')
                            ->success()
                            ->send();
                    } catch (RuntimeException $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->icon('gmdi-business-center-o')
                ->label($currentPartnerName)
                ->modalDescription('Are you sure you want to change the partner for this venue?')
                ->size(ActionSize::ExtraSmall)
                ->button(),
            ActionGroup::make([
                Action::make('Draft')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => VenueStatus::DRAFT,
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('Pending')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => VenueStatus::PENDING,
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('Active')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => VenueStatus::ACTIVE,
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('Suspended')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => VenueStatus::SUSPENDED,
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
                ->label($this->getRecord()->status->getLabel())
                ->icon('polaris-status-icon')
                ->color('primary')
                ->size(ActionSize::ExtraSmall)
                ->button(),
        ];
    }
}
