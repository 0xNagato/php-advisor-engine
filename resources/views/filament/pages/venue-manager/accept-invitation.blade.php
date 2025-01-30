@php use Filament\Support\Facades\FilamentView; @endphp
@php use Filament\View\PanelsRenderHook; @endphp
<x-admin.simple>
    <div>
        @if ($invitationUsedMessage)
            <div class="p-4 mb-4 text-yellow-700 bg-yellow-100 border-l-4 border-yellow-500" role="alert">
                <p class="font-bold">Invitation Status</p>
                <p>{{ $invitationUsedMessage }}</p>
            </div>
        @else
            <x-filament-panels::form wire:submit="create">
                <div class="-mt-4 text-sm text-center text-gray-600">
                    Please create your password to access the venue management platform
                </div>
                {{ $this->form }}

                <x-filament-panels::form.actions :actions="[\Filament\Actions\Action::make('create')->label('Create Account')->submit('create')]" :full-width="true" />
            </x-filament-panels::form>
        @endif
    </div>
</x-admin.simple>
