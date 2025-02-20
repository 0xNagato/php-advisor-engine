@php
    use App\Models\VipCode;
    $vipCode = Cache::remember(
        'available_calendar_button_vip_code_1',
        60,
        fn() => VipCode::query()->where('concierge_id', 1)->active()->first(),
    );
@endphp
<section
    class="mt-16 max-w-[1320px] mx-auto px-6 sm:px-8 md:px-12 rounded-2xl overflow-hidden bg-cover bg-center h-[350px] bg-indigo-500 mb-10"
    style="background-image: url('{{ asset('/images/marketing/background-4.webp') }}');">
    <div class="text-white text-center h-full flex flex-col justify-center rounded-lg">
        <!-- Title -->
        <h2 class="text-3xl font-bold mb-4">
            Become a part of the PRIMA community today.
        </h2>
        <!-- Text -->
        <p class="text-lg mb-8">
            Experience exclusive dining opportunities and be a part of our mission to enhance the restaurant
            industry.
        </p>
        <!-- Buttons Section -->
        <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-4 sm:space-y-0 sm:justify-center w-full">
            <!-- Talk to PRIMA Button -->
            <button @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })"
                class="bg-gradient-to-b from-[#8B37E4] to-[#DE5A59] text-white px-6 py-3 rounded-md border-[3px] border-white hover:bg-opacity-90 w-full sm:w-auto">
                Talk to PRIMA
            </button>
            <!-- Book Your Dining Experience Button -->
            <a href="{{ route('v.booking', ['code' => $vipCode->code]) }}"
                class="bg-gradient-to-b from-[#34AFF1] to-[#3954C7] text-white px-6 py-3 rounded-md border-[3px] border-white hover:bg-opacity-90 w-full sm:w-auto">
                Book Your Dining Experience
            </a>
        </div>
    </div>
</section>
