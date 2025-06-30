@php
    use App\Enums\VenueStatus;
    use Carbon\Carbon;

    $stickyHeaderTopPosition = isPrimaApp() ? 'top-0' : 'top-16';
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div>
        @if (filled($venues))
            @include('partials.availability-table')
        @else
            @include('partials.availability-empty-state')
        @endif
    </div>
    <style>
        .gap-6 {
            gap: 1rem;
        }
    </style>
</x-filament-panels::page>
