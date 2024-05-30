<?php

namespace App\Livewire\Restaurant;

use App\Filament\Resources\RestaurantResource\Components\RestaurantContactsForm;
use App\Models\Restaurant;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

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

        $this->form->fill([
            'contacts' => $this->restaurant->contacts->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Repeater::make('contacts')
                ->reorderable(false)
                ->addActionLabel('Add Contact')
                ->hiddenLabel()
                ->label('Contacts')
                ->schema(
                    RestaurantContactsForm::schema()
                ),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->restaurant->update([
            'contacts' => $data['contacts'],
        ]);

        Notification::make()
            ->title('Contacts updated successfully.')
            ->success()
            ->send();
    }
}
