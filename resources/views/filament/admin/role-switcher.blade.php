<div @class(['hidden' => $profiles->count() <= 1])>
    <div class="flex items-center">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="flex items-center whitespace-nowrap gap-2 pl-2 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                <span>{{ formatRoleName($profiles->firstWhere('is_active', true)?->role->name ?? 'Select Role') }}</span>
                <x-heroicon-m-chevron-down class="w-4 h-4 text-indigo-600" />
            </button>

            <div x-show="open" @click.away="open = false"
                class="absolute left-0 z-10 w-48 mt-1 origin-top-right bg-white border border-gray-300 rounded-lg shadow-lg"
                role="menu">
                <div role="none">
                    @foreach ($profiles as $profile)
                        <button wire:click="switchProfile({{ $profile->id }})" type="button"
                            @class([
                                'flex items-center w-full px-4 py-2 text-xs whitespace-nowrap text-left',
                                'bg-indigo-50 text-indigo-600 font-medium' => $profile->is_active,
                                'text-gray-700 hover:bg-indigo-50 hover:text-indigo-600' => !$profile->is_active,
                                'rounded-t-lg' => $loop->first,
                                'rounded-b-lg' => $loop->last,
                            ]) role="menuitem">
                            <span class="flex-grow">{{ formatRoleName($profile->role->name) }}</span>
                            @if ($profile->is_active)
                                <x-heroicon-m-check class="w-4 h-4 ml-2 text-indigo-600" />
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
