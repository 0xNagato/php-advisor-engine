@props(['id', 'maxWidth' => '2xl', 'heading' => null, 'closeButton' => true])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        '6xl' => 'sm:max-w-6xl',
        '7xl' => 'sm:max-w-7xl',
    ][$maxWidth];
@endphp

<div x-data="{ primaModal: false }"
    x-on:prima-close-modal.window="if ($event.detail.id === '{{ $id }}') primaModal = false"
    x-on:prima-open-modal.window="if ($event.detail.id === '{{ $id }}') primaModal = true" x-show="primaModal"
    class="fixed inset-0 z-[9999] flex items-center justify-center md:items-start md:pt-[15vh]" style="display: none;">
    {{-- Backdrop with white transparency --}}
    <div x-show="primaModal" class="fixed inset-0 bg-white/60" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="primaModal = false"></div>

    {{-- Modal Panel --}}
    <div x-show="primaModal"
        class="relative bg-white rounded-lg shadow-[0_25px_50px_-12px_rgba(0,0,0,0.7)] border border-gray-300 transform transition-all w-full {{ $maxWidth }} mx-4"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.stop>
        @if ($heading)
            <div class="px-6 pt-4">
                <h3 class="text-lg font-semibold">
                    {{ $heading }}
                </h3>
                @if ($closeButton)
                    <button @click="primaModal = false"
                        class="absolute text-gray-400 top-4 right-4 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        @endif

        <div class="px-6 py-4">
            {{ $slot }}
        </div>
    </div>
</div>
