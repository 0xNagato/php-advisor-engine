@php
    use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
    use Illuminate\Support\Facades\Auth;
@endphp

@props(['booking'])

<div class="flex items-center sm:text-sm text-xs">
    @php
        $concierges = [
            $booking->concierge,
            $booking->concierge?->referringConcierge,
            $booking->concierge?->referringConcierge?->referringConcierge,
        ];
    @endphp
    @foreach ($concierges as $index => $concierge)
        @if ($concierge)
            @if (Auth::user()->hasActiveRole('concierge'))
                {{ $concierge->user->name }}
            @else
                <a href="{{ ViewConcierge::getUrl([$concierge->id]) }}">
                    {{ $concierge->user->name }}
                </a>
            @endif
            @if ($index < count($concierges) - 1 && $concierges[$index + 1])
                <x-gmdi-chevron-left-s class="w-4 h-4" />
            @endif
        @endif
    @endforeach
</div>
