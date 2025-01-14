<?php

namespace App\Traits;

use App\Actions\Partner\InviteConciergeViaSms;
use App\Models\Region;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

trait HandlesBulkInviteConciergeInvitations
{
    use FormatsPhoneNumber;

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('bulkInvite')
                ->label('Bulk Invite')
                ->size(ActionSize::ExtraSmall)
                ->icon('gmdi-contact-page')
                ->fillForm(['contacts' => ''])
                ->modalSubmitActionLabel('Invite Concierges')
                ->steps([
                    Step::make('Import')
                        ->description('Import concierges names and phone numbers.')
                        ->schema([
                            Select::make('region_id')
                                ->label('Region')
                                ->placeholder('Select Region')
                                ->options(Region::query()->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            Textarea::make('import')
                                ->hiddenLabel()
                                ->rows(3)
                                ->extraInputAttributes(['class' => 'text-sm'])
                                ->placeholder("John Doe, +12125551234, Hilton Hotel\nJane Smith, +12125559876, Marriott")
                                ->hint('Format: Name, Phone, Hotel Name (separate each contact with a new line)'),
                        ])
                        ->afterValidation(function (Get $get, Set $set) {
                            $contacts = collect(explode("\n", (string) $get('import')))
                                ->map(function ($contact) use ($get) {
                                    $contact = trim($contact);
                                    if (filled($contact)) {
                                        $parts = explode(',', $contact);
                                        $name = trim($parts[0]);
                                        $phone = $this->getInternationalFormattedPhoneNumber(trim($parts[1]));
                                        $companyName = trim($parts[2] ?? '');

                                        $nameParts = explode(' ', $name);
                                        $firstName = $nameParts[0];
                                        $lastName = $nameParts[1] ?? '';

                                        return [
                                            'first_name' => $firstName,
                                            'last_name' => $lastName,
                                            'phone' => $phone,
                                            'region_id' => $get('region_id'),
                                            'company_name' => $companyName,
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
                                        ->hiddenLabel()
                                        ->onlyCountries(config('app.countries'))
                                        ->displayNumberFormat(PhoneInputNumberType::E164)
                                        ->disallowDropdown()
                                        ->validateFor(
                                            country: config('app.countries'),
                                            type: PhoneNumberType::MOBILE,
                                            lenient: true,
                                        )
                                        ->columnSpan(2),
                                    Select::make('region_id')
                                        ->label('Region')
                                        ->options(Region::query()->pluck('name', 'id'))
                                        ->searchable()
                                        ->columnSpan(2),
                                ])
                                ->extraAttributes(['class' => 'inline-form'])
                                ->columns(['default' => 2]),
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
