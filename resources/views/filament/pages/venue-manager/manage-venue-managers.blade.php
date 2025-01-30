<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-4">
            <p>
                Invite additional managers to help manage venues for {{ $this->venueGroup->name }}. You can control
                which venues each manager has access to.
            </p>

            <p class="text-sm text-gray-600">
                Note: Invited managers will receive an email with instructions to create their account and access the
                platform.
            </p>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
