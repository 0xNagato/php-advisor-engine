<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Enums\RestaurantStatus;
use App\Filament\Resources\RestaurantResource;
use App\Filament\Resources\RestaurantResource\Components\RestaurantContactsForm;
use App\Models\Partner;
use App\Models\Restaurant;
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
use Illuminate\Contracts\Support\Htmlable;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * Class EditRestaurant
 *
 * @method Restaurant getRecord()
 */
class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->restaurant_name;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Restaurant Information')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        FileUpload::make('restaurant_logo_path')
                            ->label('Restaurant Logo')
                            ->disk('do')
                            ->directory('restaurants')
                            ->visibility('public'),
                        TextInput::make('restaurant_name')
                            ->label('Restaurant Name')
                            ->required()
                            ->maxLength(255),
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

                    ]),
                Repeater::make('contacts')
                    ->columnSpanFull()
                    ->addActionLabel('Add Contact')
                    ->label('Contacts')
                    ->schema(
                        RestaurantContactsForm::schema()
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
                        TextInput::make('payout_restaurant')
                            ->label('Payout Restaurant')
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
        $currentPartnerName = Partner::find($this->getRecord()->user->partner_referral_id)->user->name;

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
                    $this->getRecord()->updateReferringPartner($data['new_partner_id']);

                    Notification::make()
                        ->title('Partner Changed Successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->icon('gmdi-business-center-o')
                ->label($currentPartnerName)
                ->button(),
            ActionGroup::make([
                Action::make('Draft')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => RestaurantStatus::DRAFT,
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('Pending')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => RestaurantStatus::PENDING,
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('Active')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => RestaurantStatus::ACTIVE,
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('Suspended')
                    ->action(function () {
                        $this->getRecord()->update([
                            'status' => RestaurantStatus::SUSPENDED,
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
                ->label($this->getRecord()->status->getLabel())
                ->icon('polaris-status-icon')
                ->color('primary')
                ->button(),
        ];
    }
}
