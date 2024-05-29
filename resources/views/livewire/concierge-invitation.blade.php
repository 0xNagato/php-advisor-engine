@php use Filament\Support\Facades\FilamentView; @endphp
@php use Filament\View\PanelsRenderHook; @endphp
<x-admin.simple>
    <x-filament-panels::form wire:submit="secureAccount">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>

    <div class="h-0">
        <x-filament::modal id="concierge-modal" width="3xl">
            <x-slot name="heading">

            </x-slot>
            @include('terms.concierge-terms')
        </x-filament::modal>
    </div>
</x-admin.simple>
