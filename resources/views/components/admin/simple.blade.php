@php use Filament\Tables\Contracts\HasTable; @endphp
@props([
    'heading' => null,
    'subheading' => null,
    'size' => 'md',
    'showWrapper' => true
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
    <div class="w-full text-3xl font-bold text-center pt-6 leading-5 tracking-tight text-gray-950">
        PRIMA
    </div>
    <div class="dm-serif text-2xl p-2 mb-4 text-center font-bold tracking-tight text-gray-950">
        Everybody Wins
    </div>

    @if ($showWrapper)
        <div class="bg-white px-6 pt-8 pb-8 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 {{ $containerSize }} sm:mx-auto">
            @endif
            <div class="font-semibold text-lg mb-4 text-center">
                {{ $this->getHeading() }}
            </div>

            {{ $slot }}
            @if ($showWrapper)
        </div>
    @endif

    @if (!$this instanceof HasTable)
        <x-filament-actions::modals/>
    @endif

    @if(request()->route()->uri() === 'platform/login')
        <div class="items-end justify-center text-sm text-center mt-4">
            <a class="font-bold " href="{{ route('venue.login') }}">Venue Admin Login</a>
        </div>
    @endif

    <script>
        if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify({
                route: '{{ request()->route()->uri() }}',
            }));
        }
    </script>

    <div class="flex items-end justify-center text-sm text-center mt-4">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>
</div>
