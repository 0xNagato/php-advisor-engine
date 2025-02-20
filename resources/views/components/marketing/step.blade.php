@props(['step', 'index'])

<div class="flex-shrink-0 w-full transition-all duration-300 ease-out"
     :style="isMobile ? { transform: `translateX(calc(-${currentSlide * 100}% + ${dragOffset}px))` } : {}">
    <div class="flex flex-col items-center box_grid-item">
        <div class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] md:h-[300px] w-full px-5 pt-4 pb-8 md:pb-12">
            <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots" class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
            <img src="{{ asset($step['image']) }}" alt="reservation-picture" class="relative z-[1] h-full w-auto object-contain">
        </div>
        <div class="my-6 box_grid-numering">
            <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left" class="w-auto">
            <div class="box_grid-numering_text">{{ $index + 1 }}</div>
            <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right" class="w-auto">
        </div>
        <div class="px-4 pb-6 box_grid-info md:px-6">
            <p class="mb-3 box_grid-title">{{ $step['title'] }}</p>
            <p class="box_grid-description">{{ $step['description'] }}</p>
        </div>
    </div>
</div>
