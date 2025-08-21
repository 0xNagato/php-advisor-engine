{{-- file: `resources/views/components/two-line-address.blade.php` --}}
@props(['address'])

@php
    $address = (string) ($address ?? '');
    $hasNewline = preg_match("/\r\n|\r|\n/", $address) === 1;
@endphp

@if($hasNewline)
    {!! nl2br(e($address)) !!}
@else
    @php
        $parts = \Illuminate\Support\Str::of($address)
            ->explode(',')
            ->map(fn($s) => trim($s))
            ->filter()
            ->values();

        if ($parts->count() >= 2) {
            $line2 = $parts->slice(-2)->implode(', ');
            $line1 = $parts->slice(0, $parts->count() - 2)->implode(', ');
            if ($line1 !== '') { $line1 .= ','; }
        } else {
            $line1 = $address;
            $line2 = null;
        }
    @endphp

    @if($line1 !== '')
        <span>{{ $line1 }}</span>
    @endif
    @if($line2)
        <br>
        <span>{{ $line2 }}</span>
    @endif
@endif
