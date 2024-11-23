<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Send SMS
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

@push('styles')
    <style>
        .fi-placeholder {
            --tw-border-opacity: 1;
            border: 1px solid rgb(226 232 240 / var(--tw-border-opacity));
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 0.5rem;
            background-color: rgb(249 250 251);
        }

        .fi-placeholder-content {
            line-height: 1.5;
        }
    </style>
@endpush
