@php
    use App\Enums\VenueStatus;
    use Carbon\Carbon;

    $stickyHeaderTopPosition = isPrimaApp() ? 'top-0' : 'top-16';
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div>
        @if ($this->isDateBeyondLimit())
            <div class="text-center py-12">
                <div class="mx-auto max-w-md">
                    <div class="rounded-lg bg-gray-50 p-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Date Too Far in Advance</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            We only show availability for the next {{ $this->getMaxReservationDays() }} days. 
                            Please select a date within this range.
                        </p>
                    </div>
                </div>
            </div>
        @elseif (filled($venues))
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
