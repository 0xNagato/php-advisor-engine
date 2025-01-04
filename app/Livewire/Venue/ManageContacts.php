<?php

namespace App\Livewire\Venue;

use App\Filament\Resources\VenueResource\Components\VenueContactsForm;
use App\Models\Venue;
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

    protected static ?string $pollingInterval = null;

    protected static string $view = 'livewire.venue.manage-contacts';

    public Venue $venue;

    public ?array $data = [];

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;

        $this->form->fill([
            'contacts' => $this->venue->contacts?->toArray(),
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
                    VenueContactsForm::schema()
                ),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->venue->update([
            'contacts' => $data['contacts'],
        ]);

        Notification::make()
            ->title('Contacts updated successfully.')
            ->success()
            ->send();
    }
}
