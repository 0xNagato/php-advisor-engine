<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class ManageContacts extends Page
{
    protected static ?string $navigationIcon = 'gmdi-people-o';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.manage-contacts';

    public array $data;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->form->fill(['contacts' => auth()->user()->restaurant->contacts->toArray()]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Contacts')->schema(
                [
                    Repeater::make('contacts')
                        ->reorderable(false)
                        ->addActionLabel('Add Contact')
                        ->hiddenLabel()
                        ->label('Contacts')
                        ->schema([
                            TextInput::make('contact_name')
                                ->label('Contact Name')
                                ->required(),
                            PhoneInput::make('contact_phone')
                                ->label('Contact Phone')
                                ->required()
                                ->onlyCountries(['US', 'CA'])
                                ->initialCountry('US'),
                            Checkbox::make('use_for_reservations')
                                ->label('Use for Reservations')
                                ->default(true),
                        ]),

                ]
            ),
        ])->statePath('data');
    }

    public function save(): void
    {
        auth()->user()->restaurant->update([
            'contacts' => $this->data['contacts'],
        ]);

        Notification::make()
            ->title('Contacts updated successfully.')
            ->success()
            ->send();
    }
}
