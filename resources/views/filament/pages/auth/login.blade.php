@php use Filament\Support\Facades\FilamentView; @endphp
@php use Filament\View\PanelsRenderHook; @endphp
<x-admin.simple>
    @if (request()->boolean('token_reset'))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Please try in again. For your security reasons, we refreshed the login access token.
        </div>
    @endif
    @if (isPrimaApp())
        <div class="p-4 text-center">
            @if (auth()->check())
                <p class="mb-4">
                    Oops! You've reached this page by mistake. Please use the app's menu to navigate to the
                    desired page.
                </p>
            @else
                <p class="mb-4">
                    Your session has expired. Please use the app's menu to log out and then log in again.
                </p>
            @endif
        </div>
    @else
        @if (filament()->hasRegistration())
            <x-slot name="subheading">
                {{ __('filament-panels::pages/auth/login.actions.register.before') }}

                {{ $this->registerAction }}
            </x-slot>
        @endif

        {{ FilamentView::renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <x-filament-panels::form wire:submit="authenticate">
            {{ $this->form }}

            <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
                                             :full-width="$this->hasFullWidthFormActions()" />
        </x-filament-panels::form>

        {{ FilamentView::renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
    @endif
</x-admin.simple>

<script>
    // Livewire v3: intercept failed requests and suppress the default 419 confirm modal
    // Reload with a friendly notice query string.
    document.addEventListener('livewire:init', () => {
        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        const url = new URL(window.location.href);
                        url.searchParams.set('token_reset', '1');
                        window.location.replace(url.toString());
                    }
                });
            });
        }
    });
</script>
