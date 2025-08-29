<?php

namespace App\Livewire\Concierge;

use App\Models\VenueCollection;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Livewire\Component;

class EditVenueCollectionForm extends Component
{
    use InteractsWithForms;

    public VenueCollection $collection;

    public ?array $data = [];

    public function mount(VenueCollection $collection): void
    {
        $this->collection = $collection;
        $this->form->fill([
            'name' => $collection->name,
            'description' => $collection->description,
            'is_active' => $collection->is_active,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Collection Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('My Curated Venues'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('A curated selection of the best venues...'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Enable or disable this collection'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->collection->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Collection updated successfully!',
        ]);

        $this->dispatch('close-modal');
        $this->dispatch('refresh-table');
    }

    public function render()
    {
        return view('livewire.concierge.edit-venue-collection-form');
    }
}
