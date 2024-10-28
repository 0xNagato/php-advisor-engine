<div class="flex items-center">
    <a href="#" x-data="" x-on:click.prevent="$dispatch('open-modal', { id: 'contact-us-modal' })"
        class="-mr-2 inline-flex items-center justify-center w-8 h-8 text-sm text-white bg-indigo-800 rounded-full sm:rounded-md sm:w-auto sm:h-auto sm:px-2 sm:py-1.5 hover:bg-indigo-700">
        <span class="text-base font-semibold">?</span>
        <span class="hidden sm:inline">Help</span>
    </a>
    <livewire:contact-form />
</div>
