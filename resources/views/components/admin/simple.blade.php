@php use Filament\Tables\Contracts\HasTable; @endphp
@props([
    'heading' => null,
    'subheading' => null,
])

<div class="pb-4">
    <div class="w-full text-3xl font-bold text-center pt-6 leading-5 tracking-tight text-gray-950">
        PRIMA
    </div>
    <div class="dm-serif text-2xl p-2 mb-4 text-center font-bold tracking-tight text-gray-950">
        Everybody Wins
        {{--<sup>&trade;</sup>--}}
    </div>

    <div class="bg-white px-6 pt-8 pb-8 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 max-w-md sm:mx-auto">
        <div class="font-semibold text-lg mb-4 text-center">
            {{ $this->getHeading() }}
        </div>

        {{ $slot }}
    </div>

    @if (! $this instanceof HasTable)
        <x-filament-actions::modals/>
    @endif

    <div class="flex items-end justify-center text-sm text-center mt-4">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>
</div>
