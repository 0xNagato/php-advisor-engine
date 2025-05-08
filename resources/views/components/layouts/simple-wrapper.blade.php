@props([
    'wrapperClass' => '',
    'headerClass' => '',
    'contentClass' => 'max-w-lg',
    'logoUrl' => false,
])

{{-- This is a simple wrapper mainly used for none logged in users with the logo and copyright --}}
<div @class([
    'flex flex-col justify-center min-h-screen p-4 antialiased wavy-background',
    $wrapperClass,
])>
    <div @class([
        'w-full text-xl font-bold leading-5 tracking-tight text-left text-gray-950',
        $headerClass,
    ])>
        @if ($logoUrl)
            <div class="flex items-center justify-between p-4">
                <a href="{{ $logoUrl }}">PRIMA</a>
                <div class="flex justify-end pt-2">
                    <livewire:availability.advance-filters />
                </div>
            </div>
        @else
            <div class="flex items-center justify-between p-4">
                <div>PRIMA</div>
                <div>
                    @auth
                        <a href="{{ route('filament.admin.pages.admin-dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Login
                        </a>
                    @endauth
                </div>
            </div>
        @endif
    </div>
    <div @class([
        'flex flex-col items-center flex-grow mt-6 w-full mx-auto',
        $contentClass,
    ])>
        {{ $slot }}
    </div>
    <div class="flex items-end justify-center mt-4 text-sm text-center">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>

    @if (isset($this) && !$this instanceof HasTable && $this instanceof \Filament\Pages\Page)
        <x-filament-actions::modals />
    @endif
</div>
