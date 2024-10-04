@php
    use App\Enums\VenueStatus;
    use Carbon\Carbon;

    $stickyHeaderTopPosition = isPrimaApp() ? 'top-0' : 'top-16';
@endphp
<x-layouts.simple-wrapper padding="p-4" maxWidth="max-w-full">
    {{ $this->form }}

    @if (filled($venues))
        @include('partials.availability-table')
    @endif

    <style>
        .fi-btn-group {
            width: 100% !important;
        }
    </style>
</x-layouts.simple-wrapper>
