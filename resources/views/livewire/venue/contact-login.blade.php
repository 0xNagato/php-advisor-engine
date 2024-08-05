<x-admin.simple>
    <x-filament-panels::form wire:submit="create">
       @if(! $this->linkSent)
            <div class="text-sm text-gray-600 -mt-4 text-center">
                {{  $this->getSubheading() }}
            </div>

            {{ $this->form }}

            <x-filament-panels::form.actions :actions="$this->getFormActions()" :full-width="true"></x-filament-panels::form.actions>
           @else
           <p class="text-sm text-gray-600 -mt-4 text-center">
                {{ $this->message }}
           </p>
        @endif
    </x-filament-panels::form>
</x-admin.simple>
