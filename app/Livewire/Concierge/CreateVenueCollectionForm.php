<?php

namespace App\Livewire\Concierge;

use App\Models\VenueCollection;
use App\Models\VipCode;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Livewire\Component;

class CreateVenueCollectionForm extends Component
{
    use InteractsWithForms;

    public VipCode $vipCode;

    public ?array $data = [];

    public function mount(VipCode $vipCode): void
    {
        $this->vipCode = $vipCode;
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
                    ->helperText('Enable or disable this collection')
                    ->default(true),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        VenueCollection::query()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
            'vip_code_id' => $this->vipCode->id,
            'concierge_id' => null,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Collection created successfully!',
        ]);

        $this->dispatch('close-modal');
        $this->dispatch('refresh-table');
    }

    public function render()
    {
        return view('livewire.concierge.create-venue-collection-form');
    }
}
