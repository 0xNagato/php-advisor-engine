<div class="space-y-4">
    <div class="flex items-center gap-x-3">
        <input type="text" value="{{ $shortUrl }}"
            id="announcement-url"
            class="flex-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
            readonly>
        <x-filament::button
            type="button"
            x-data="{ copied: false }"
            x-on:click="
                navigator.clipboard.writeText($el.previousElementSibling.value);
                copied = true;
                // The visual feedback in the button is sufficient
                setTimeout(() => copied = false, 2000);
            "
        >
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak class="text-green-500 flex items-center">
                <x-heroicon-m-check class="w-4 h-4 mr-1" />
                Copied!
            </span>
        </x-filament::button>
    </div>
    <p class="text-sm text-gray-500">
        When partners or concierges visit this link, they'll see the full announcement message.
    </p>
</div>