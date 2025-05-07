<!--suppress CssUnusedSymbol -->
@php
    use App\Enums\VenueStatus;
    use Carbon\Carbon;

    $stickyHeaderTopPosition = 'top-0';
@endphp

<x-layouts.simple-wrapper content-class="max-w-3xl" logo-url="{{ url('/') }}">
    <div class="w-full mb-6">
        <livewire:vip.region-selector />
    </div>
    
    <div class="w-full">
        {{ $this->form }}
    </div>

    @if (filled($venues))
        <div class="w-full mt-6">
            @include('partials.availability-table')
        </div>
    @else
        <div class="mt-8">
            @include('partials.availability-empty-state')
        </div>
    @endif

    <style>
        .fi-btn-group {
            width: 100% !important;
        }
        
        .inline-form {
            gap: 1rem !important;
            column-gap: 0.5rem !important;
        }
    </style>
</x-layouts.simple-wrapper>
