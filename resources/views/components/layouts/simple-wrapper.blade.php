{{-- This is a simple wrapper mainly used for none logged in users with the logo and copyright --}}
<div class="flex flex-col justify-center min-h-screen p-4 antialiased wavy-background">
    <div class="w-full text-xl font-bold leading-5 tracking-tight text-left text-gray-950">
        PRIMA
    </div>
    <div class="flex flex-col items-center flex-grow w-full max-w-lg mx-auto mt-6">
        {{ $slot }}
    </div>
    <div class="flex items-end justify-center mt-4 text-sm text-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>

    @if (isset($this) && !$this instanceof HasTable)
        <x-filament-actions::modals />
    @endif
</div>
