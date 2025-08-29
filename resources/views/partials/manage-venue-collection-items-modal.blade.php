<div class="space-y-6">
    <div class="text-sm text-gray-600">
        <p>Manage venues in the collection "<strong>{{ $collection->name }}</strong>". Add, remove, or reorder venues and add notes for each.</p>
    </div>

    <livewire:concierge.manage-venue-collection-items :collection="$collection" />
</div>
