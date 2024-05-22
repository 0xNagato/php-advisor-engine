<?php

namespace App\Livewire\Restaurant;

use App\Models\Restaurant;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * @property Form $form
 */
class ManageContacts extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.restaurant.manage-contacts';

    public Restaurant $restaurant;

    public ?array $data = [];

    public function mount(Restaurant $restaurant): void
    {
        $this->restaurant = $restaurant;
        $this->form->fill(['contacts' => $this->restaurant->contacts->toArray()]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
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
                        ->onlyCountries(config('app.countries'))
                        ->displayNumberFormat(PhoneInputNumberType::E164)
                        ->disallowDropdown()
                        ->validateFor(
                            country: config('app.countries'),
                            type: PhoneNumberType::MOBILE,
                            lenient: true,
                        )
                        ->initialCountry('US'),
                    Checkbox::make('use_for_reservations')
                        ->label('Use for Reservations')
                        ->extraAttributes(['class' => 'text-indigo-600'])
                        ->default(true),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $this->restaurant->update([
            'contacts' => $this->data['contacts'],
        ]);

        Notification::make()
            ->title('Contacts updated successfully.')
            ->success()
            ->send();
    }
}
