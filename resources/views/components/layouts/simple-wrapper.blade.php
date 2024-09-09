{{-- This is a simple wrapper mainly used for none logged in users with the logo and copyright --}}
<div class="flex min-h-screen flex-col justify-center p-4 antialiased wavy-background">
    <div class="w-full text-left text-xl font-bold leading-5 tracking-tight text-gray-950">
        PRIMA
    </div>
    <div class="mx-auto mt-6 flex w-full max-w-lg flex-grow flex-col items-center">
        {{ $slot }}
    </div>
    <div class="mt-4 flex items-end justify-center text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>

    @if (isset($this) && !$this instanceof HasTable)
        <x-filament-actions::modals/>
    @endif
</div>
