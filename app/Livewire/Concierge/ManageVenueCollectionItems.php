<?php

namespace App\Livewire\Concierge;

use App\Models\Venue;
use App\Models\VenueCollection;
use App\Models\VenueCollectionItem;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Livewire\Component;

class ManageVenueCollectionItems extends Component
{
    use InteractsWithForms;

    public VenueCollection $collection;

    public ?array $data = [];

    public function mount(VenueCollection $collection): void
    {
        $this->collection = $collection;

        // Load existing items data
        $itemsData = $collection->items()->with('venue')->get()->map(fn ($item) => [
            'id' => $item->id,
            'venue_id' => $item->venue_id,
            'note' => $item->note,
            'is_active' => $item->is_active,
        ])->toArray();

        $this->form->fill(['venues' => $itemsData]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('venues')
                    ->label('Venues in Collection')
                    ->schema([
                        Select::make('venue_id')
                            ->label('Venue')
                            ->options(Venue::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Textarea::make('note')
                            ->label('Note/Review')
                            ->rows(2)
                            ->placeholder('Add a note, review, or recommendation...'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->orderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => Venue::query()->find($state['venue_id'])?->name ?? 'Unknown Venue'
                    )
                    ->addActionLabel('Add Venue')
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (isset($data['venues'])) {
            $this->saveVenueCollectionItems($data['venues']);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Venues updated successfully!',
        ]);

        $this->dispatch('close-modal');
        $this->dispatch('refresh-table');
    }

    protected function saveVenueCollectionItems(array $venuesData): void
    {
        // Get existing item IDs to track what to delete
        $existingItemIds = $this->collection->items()->pluck('id')->toArray();
        $updatedItemIds = [];

        foreach ($venuesData as $venueData) {
            if (isset($venueData['venue_id'])) {
                $item = VenueCollectionItem::query()->updateOrCreate([
                    'id' => $venueData['id'] ?? null,
                    'venue_collection_id' => $this->collection->id,
                ], [
                    'venue_id' => $venueData['venue_id'],
                    'note' => $venueData['note'] ?? null,
                    'is_active' => $venueData['is_active'] ?? true,
                ]);

                $updatedItemIds[] = $item->id;
            }
        }

        // Delete items that were removed from the form
        $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
        if (! empty($itemsToDelete)) {
            VenueCollectionItem::query()->whereIn('id', $itemsToDelete)->delete();
        }
    }

    public function render()
    {
        return view('livewire.concierge.manage-venue-collection-items');
    }
}
