@php use Filament\Support\Facades\FilamentView; @endphp
@php use Filament\View\PanelsRenderHook; @endphp
<x-admin.simple>

    {{ FilamentView::renderHook(PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form wire:submit="resetPassword">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ FilamentView::renderHook(PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    <div class="h-0">
        <x-filament::modal id="restaurant-modal">
            <x-slot name="heading">

            </x-slot>
            @include('terms.restaurant-terms')
        </x-filament::modal>

        <x-filament::modal id="concierge-modal">
            <x-slot name="heading">

            </x-slot>
            @include('terms.concierge-terms')
        </x-filament::modal>
    </div>
</x-admin.simple>
