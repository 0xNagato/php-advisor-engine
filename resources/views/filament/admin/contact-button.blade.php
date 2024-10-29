<div class="flex items-center">
    <a href="#" x-data="" x-on:click.prevent="$dispatch('open-modal', { id: 'contact-us-modal' })"
        class="inline-flex items-center justify-center w-8 h-8 -mr-2 text-sm text-white bg-indigo-800 rounded-full hover:bg-indigo-700">
        <span class="text-base font-semibold">?</span>
    </a>
    <livewire:contact-form />
</div>
