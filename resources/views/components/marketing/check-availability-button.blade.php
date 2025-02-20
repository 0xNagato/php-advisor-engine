<a href="{{ route('v.booking', ['code' => $vipCode->code]) }}"
   class="bg-gradient-to-b from-[#7B34F1] to-[#3954C7] text-white font-sans text-base font-medium leading-normal rounded-[8px] px-5 py-3.5 inline-block">
    @if ($slot->isEmpty())
        Check Availability Now
    @else
        {{ $slot }}
    @endif
</a>
