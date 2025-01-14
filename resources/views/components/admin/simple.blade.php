@php use Filament\Tables\Contracts\HasTable; @endphp
@props([
    'heading' => null,
    'subheading' => null,
    'size' => 'md',
    'showWrapper' => true,
])

@php
    $containerSize = [
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-4xl',
    ][$size];
@endphp

<div class="pb-4">
    <div class="w-full pt-6 text-3xl font-bold leading-5 tracking-tight text-center text-gray-950">
        PRIMA
    </div>
    <div class="p-2 mb-4 text-2xl font-bold tracking-tight text-center dm-serif text-gray-950">
        Everybody Wins<span class="font-sans font-normal">™</span>
    </div>

    @if ($showWrapper)
        <div
            class="bg-white px-6 pt-8 pb-8 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 {{ $containerSize }} sm:mx-auto">
    @endif
    <div class="mb-4 text-lg font-semibold text-center">
        {{ $this->getHeading() }}
    </div>

    {{ $slot }}
    @if ($showWrapper)
</div>
@endif

@if (!$this instanceof HasTable)
    <x-filament-actions::modals />
@endif

<script>
    if (window.ReactNativeWebView) {
        window.ReactNativeWebView.postMessage(JSON.stringify({
            route: '{{ request()->route()->uri() }}',
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }}
        }));
    }
</script>

<div class="flex items-end justify-center mt-4 text-sm text-center">
    &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
</div>
</div>
