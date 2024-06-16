<?php

namespace App\Filament\Pages\Partner;

use App\Actions\Partner\InviteConciergeViaSms;
use App\Filament\Pages\Concierge\ConciergeReferral;
use App\Traits\FormatsPhoneNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class PartnerConcierges extends ConciergeReferral
{
    use FormatsPhoneNumber;

    protected static ?string $navigationIcon = 'govicon-user-suit';

    protected static string $view = 'filament.pages.partner.partner-concierge-referrals';

    protected static ?string $title = 'My Concierges';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'partner/concierge';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('partner');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkInvite')
                ->label('Bulk Invite')
                ->fillForm([
                    'contacts' => '',
                ])
                ->modalSubmitActionLabel('Invite Concierges')
                ->steps([
                    Step::make('Import')
                        ->description('Import concierges names and phone numbers.')
                        ->schema([
                            Textarea::make('import')
                                ->hiddenLabel()
                                ->rows(3)
                                ->placeholder("John Doe, +12125551234\nJane Smith, +12125559876")
                                ->hint('Add a comma after the name and separate each contact with a new line.'),
                        ])
                        ->afterValidation(function (Get $get, Set $set) {
                            $contacts = collect(explode("\n", (string) $get('import')))
                                ->map(function ($contact) {
                                    $contact = trim($contact);
                                    if (filled($contact)) {
                                        $contact = explode(',', $contact);
                                        $name = trim($contact[0]);
                                        $phone = $this->getInternationalFormattedPhoneNumber(trim($contact[1]));

                                        $nameParts = explode(' ', $name);
                                        $firstName = $nameParts[0];
                                        $lastName = $nameParts[1] ?? '';

                                        return [
                                            'first_name' => $firstName,
                                            'last_name' => $lastName,
                                            'phone' => $phone,
                                        ];
                                    }

                                    return null;
                                })
                                ->filter();

                            $set('contacts', $contacts->toArray());
                        }),
                    Step::make('Review')
                        ->description('Review the contacts you are about to invite.')
                        ->schema([
                            Repeater::make('contacts')
                                ->label('Concierges')
                                ->reorderable(false)
                                ->addActionLabel('Add Concierge')
                                ->schema([
                                    TextInput::make('first_name')
                                        ->hiddenLabel()
                                        ->columnSpan(1),
                                    TextInput::make('last_name')
                                        ->hiddenLabel()
                                        ->columnSpan(1),
                                    PhoneInput::make('phone')
                                        ->hiddenLabel()->onlyCountries(config('app.countries'))
                                        ->displayNumberFormat(PhoneInputNumberType::E164)
                                        ->disallowDropdown()
                                        ->validateFor(
                                            country: config('app.countries'),
                                            type: PhoneNumberType::MOBILE,
                                            lenient: true,
                                        )
                                        ->columnSpan(2),
                                ])
                                ->extraAttributes(['class' => 'inline-form'])
                                ->columns([
                                    'default' => 2,
                                ]),
                        ]),
                ])
                ->action(function (array $data) {
                    $contacts = collect($data['contacts']);
                    $contacts->each(fn ($contact) => InviteConciergeViaSms::run($contact));

                    $this->dispatch('concierge-referred');

                    Notification::make()
                        ->title('Concierge Invitations Sent')
                        ->success()
                        ->send();
                })
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
