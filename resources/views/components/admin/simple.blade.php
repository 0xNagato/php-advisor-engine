@php use Filament\Tables\Contracts\HasTable; @endphp
@props([
    'heading' => null,
    'subheading' => null,
])

<div>
    <div class="w-full text-3xl font-black text-center pt-4">
        PRIMA
    </div>
    <div class="sanomat-font text-2xl p-2 mb-4 text-center font-black">
        Everybody Wins&trade;
    </div>

    <div class="bg-white px-6 pt-8 pb-12 shadow-sm ring-1 ring-gray-950/5 rounded mx-4 max-w-md sm:mx-auto">
        <div class="font-semibold text-lg mb-4 text-center">
            {{ $this->getHeading() }}
        </div>

        {{ $slot }}
    </div>
    {{--    <section class="grid auto-cols-fr gap-y-6">--}}
    {{--        --}}
    {{--    </section>--}}

    {{--    @if (! $this instanceof HasTable)--}}
    {{--        <x-filament-actions::modals/>--}}
    {{--    @endif--}}
</div>
