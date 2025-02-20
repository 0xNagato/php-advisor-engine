@props([
    'title' => 'Effortless Reservation Management with PRIMA',
    'description' =>
        'Maximize your revenue by filling prime-time slots and reducing no-shows. PRIMA connects your restaurant with trusted concierges, ensuring reliable bookings and high-value clientele.',
])

<section class="fade-in mt-[60px] md:mt-[80px]" x-data="{
    currentSlide: 0,
    dragOffset: 0,
    touchStartX: 0,
    isMobile: window.innerWidth < 768,

    init() {
        window.addEventListener('resize', () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth < 768;

            // Reset position when switching between mobile and desktop
            if (wasMobile !== this.isMobile) {
                this.currentSlide = 0;
                this.dragOffset = 0;
                this.touchStartX = null;
            }
        });
    },

    next() {
        if (!this.isMobile) return;
        if (this.currentSlide < 2) this.currentSlide++;
    },

    prev() {
        if (!this.isMobile) return;
        if (this.currentSlide > 0) this.currentSlide--;
    },

    handleTouchStart(e) {
        if (!this.isMobile) return;
        this.touchStartX = e.touches ? e.touches[0].clientX : e.clientX;
    },

    handleTouchMove(e) {
        if (!this.isMobile || !this.touchStartX) return;
        const currentX = e.touches ? e.touches[0].clientX : e.clientX;
        this.dragOffset = currentX - this.touchStartX;
    },

    handleTouchEnd() {
        if (!this.isMobile) return;
        if (Math.abs(this.dragOffset) > 50) {
            if (this.dragOffset > 0) {
                this.prev();
            } else {
                this.next();
            }
        }
        this.dragOffset = 0;
        this.touchStartX = null;
    }
}">
    <div class="container">
        <div class="flex flex-col items-start gap-4 md:text-center md:items-center">
            <span class="gap-2 pl-2 tag">
                <img src="{{ asset('images/marketing/play_icon.svg') }}" alt="play-icon">
                How It Works
            </span>
            <h2 class="m-auto text-dark">
                {{ $title }}
            </h2>
            <p class="mb-6 max-w-[1090px]">
                {{ $description }}
            </p>

            <div class="w-full">
                <div class="relative w-full overflow-hidden" @touchstart="handleTouchStart" @touchmove="handleTouchMove"
                    @touchend="handleTouchEnd" @mousedown="handleTouchStart" @mousemove="handleTouchMove"
                    @mouseup="handleTouchEnd" @mouseleave="handleTouchEnd">
                    <div class="flex md:grid md:grid-cols-3 md:gap-8">
                        <!-- First Step -->
                        <div class="flex-shrink-0 w-full transition-all duration-300 ease-out"
                            :style="isMobile ? { transform: `translateX(calc(-${currentSlide * 100}% + ${dragOffset}px))` } : {}">
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] md:h-[300px] w-full px-5 pt-4 pb-8 md:pb-12">
                                    <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                        class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                    <img src="{{ asset('images/marketing/reservation-1.png') }}"
                                        alt="reservation-picture" class="relative z-[1] h-full w-auto object-contain">
                                </div>
                                <div class="my-6 box_grid-numering">
                                    <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                        class="w-auto">
                                    <div class="box_grid-numering_text">1</div>
                                    <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                        class="w-auto">
                                </div>
                                <div class="px-4 pb-6 box_grid-info md:px-6">
                                    <p class="mb-3 box_grid-title">Book Reservation</p>
                                    <p class="box_grid-description">
                                        Concierges use the PRIMA Hub to book prime-time reservations for a fee.
                                        Non-prime
                                        slots are
                                        booked without fees.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Second Step -->
                        <div class="flex-shrink-0 w-full transition-all duration-300 ease-out"
                            :style="isMobile ? { transform: `translateX(calc(-${currentSlide * 100}% + ${dragOffset}px))` } : {}">
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] md:h-[300px] w-full px-5 pt-4 pb-8 md:pb-12">
                                    <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                        class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                    <img src="{{ asset('images/marketing/reservation-2.png') }}"
                                        alt="reservation-picture" class="relative z-[1] h-full w-auto object-contain">
                                </div>
                                <div class="my-6 box_grid-numering">
                                    <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                        class="w-auto">
                                    <div class="box_grid-numering_text">2</div>
                                    <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                        class="w-auto">
                                </div>
                                <div class="px-4 pb-6 box_grid-info md:px-6">
                                    <p class="mb-3 box_grid-title">Restaurant Notification</p>
                                    <p class="box_grid-description">
                                        Once a reservation is successfully booked through PRIMA, we will notify the
                                        restaurant
                                        and ensure the diner is granted access in a seamless way.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Third Step -->
                        <div class="flex-shrink-0 w-full transition-all duration-300 ease-out"
                            :style="isMobile ? { transform: `translateX(calc(-${currentSlide * 100}% + ${dragOffset}px))` } : {}">
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] md:h-[300px] w-full px-5 pt-4 pb-8 md:pb-12">
                                    <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                        class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                    <img src="{{ asset('images/marketing/analytics-2.png') }}"
                                        alt="reservation-picture" class="relative z-[1] h-full w-auto object-contain">
                                </div>
                                <div class="my-6 box_grid-numering">
                                    <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                        class="w-auto">
                                    <div class="box_grid-numering_text">3</div>
                                    <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                        class="w-auto">
                                </div>
                                <div class="px-4 pb-6 box_grid-info md:px-6">
                                    <p class="mb-3 box_grid-title">Live Analytics</p>
                                    <p class="box_grid-description">
                                        Track real-time reservations, earnings, and performance through the PRIMA
                                        platform,
                                        giving you full transparency and control over your bookings.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Navigation Controls -->
                <div class="flex justify-center w-full mt-1 md:hidden">
                    <div class="flex gap-1">
                        <button @click="prev"
                            :class="{ 'bg-primary': currentSlide === 0, 'bg-gray-300': currentSlide !== 0 }"
                            class="w-1.5 h-1.5 rounded-full transition-colors"></button>
                        <button @click="currentSlide = 1"
                            :class="{ 'bg-primary': currentSlide === 1, 'bg-gray-300': currentSlide !== 1 }"
                            class="w-1.5 h-1.5 rounded-full transition-colors"></button>
                        <button @click="next"
                            :class="{ 'bg-primary': currentSlide === 2, 'bg-gray-300': currentSlide !== 2 }"
                            class="w-1.5 h-1.5 rounded-full transition-colors"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
