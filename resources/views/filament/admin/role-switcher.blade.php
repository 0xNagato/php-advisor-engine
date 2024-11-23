<div @class(['hidden' => $profiles->count() <= 1])>
    <x-filament::dropdown placement="bottom-start" teleport>
        <x-slot name="trigger">
            <button type="button"
                class="flex items-center whitespace-nowrap gap-2 pl-2 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                <span>{{ formatRoleName($profiles->firstWhere('is_active', true)?->role->name ?? 'Select Role') }}</span>
                <x-heroicon-m-chevron-down class="w-4 h-4 text-indigo-600" />
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($profiles as $profile)
                <x-filament::dropdown.list.item
                    wire:click="{{ !$profile->is_active ? 'switchProfile(' . $profile->id . ')' : '' }}" :color="$profile->is_active ? 'primary' : 'gray'"
                    @class([
                        'font-semibold' => $profile->is_active,
                        'text-xs !important' => true,
                    ])>
                    {{ formatRoleName($profile->role->name) }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
