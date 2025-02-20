@props([
    'title' => 'Effortless Reservation Management with PRIMA',
    'description' =>
        'Maximize your revenue by filling prime-time slots and reducing no-shows. PRIMA connects your restaurant with trusted concierges, ensuring reliable bookings and high-value clientele.',
])

<section class="fade-in mt-[60px] md:mt-[80px]" x-data="{
    currentSlide: 0,
    touchStartX: 0,
    touchEndX: 0,
    isSwiping: false,
    isMobile: window.innerWidth < 768,
    containerHeight: 0,
    dragOffset: 0,

    init() {
        this.updateHeight();
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth < 768;
            if (!this.isMobile) {
                this.currentSlide = 0;
                this.dragOffset = 0;
            }
            this.updateHeight();
        });
    },

    updateHeight() {
        if (!this.isMobile) {
            this.containerHeight = 'auto';
            return;
        }
        const items = document.querySelectorAll('.box_grid-item');
        const currentItem = items[this.currentSlide];
        if (currentItem) {
            this.containerHeight = `${currentItem.scrollHeight}px`;
        }
    },

    next() {
        this.currentSlide = (this.currentSlide + 1) % 3;
        this.dragOffset = 0;
        this.$nextTick(() => this.updateHeight());
    },

    prev() {
        this.currentSlide = (this.currentSlide - 1 + 3) % 3;
        this.dragOffset = 0;
        this.$nextTick(() => this.updateHeight());
    },

    handleTouchStart(e) {
        if (!this.isMobile) return;

        this.isSwiping = true;
        this.touchStartX = e.touches ? e.touches[0].clientX : e.clientX;
        this.dragOffset = 0;
    },

    handleTouchMove(e) {
        if (!this.isSwiping) return;

        const currentX = e.touches ? e.touches[0].clientX : e.clientX;
        this.dragOffset = currentX - this.touchStartX;
        e.preventDefault();
    },

    handleTouchEnd(e) {
        if (!this.isSwiping) return;

        const endX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
        const diff = this.touchStartX - endX;

        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                this.next();
            } else {
                this.prev();
            }
        } else {
            this.dragOffset = 0;
        }

        this.isSwiping = false;
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
                <div class="relative overflow-hidden box_grid md:grid md:grid-cols-3 md:gap-4"
                    :style="isMobile ? { height: `calc(${containerHeight} + 40px)` } : { height: 'auto' }"
                    @touchstart.prevent="handleTouchStart($event)" @touchend.prevent="handleTouchEnd($event)"
                    @touchmove.prevent="handleTouchMove($event)" @mousedown="handleTouchStart($event)"
                    @mousemove="handleTouchMove($event)" @mouseup="handleTouchEnd($event)"
                    @mouseleave="handleTouchEnd($event)">

                    <div class="absolute w-full transition-all duration-300 ease-out box_grid-item md:relative"
                        :class="{ 'opacity-0': isMobile && currentSlide !== 0 }"
                        :style="isMobile ? {
                            transform: `translateX(calc(${currentSlide === 0 ? 0 : currentSlide === 2 ? 100 : -100}% + ${dragOffset}px))`,
                            visibility: currentSlide === 0 ? 'visible' : 'hidden'
                        } : {}">
                        <div class="flex flex-col items-center">
                            <div
                                class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] w-full">
                                <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                    class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                <img src="{{ asset('images/marketing/reservation-1.png') }}" alt="reservation-picture"
                                    class="relative z-[1] h-full w-auto object-contain py-4">
                            </div>
                            <div class="my-6 box_grid-numering">
                                <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left" class="w-auto">
                                <div class="box_grid-numering_text">1</div>
                                <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                    class="w-auto">
                            </div>
                            <div class="px-4 pb-6 box_grid-info md:px-6">
                                <p class="mb-1 box_grid-title">Book Reservation</p>
                                <p class="box_grid-description">
                                    Concierges use the PRIMA Hub to book prime-time reservations for a fee. Non-prime
                                    slots are
                                    booked without fees.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute w-full transition-all duration-300 ease-out box_grid-item md:relative"
                        :class="{ 'opacity-0': isMobile && currentSlide !== 1 }"
                        :style="isMobile ? {
                            transform: `translateX(calc(${currentSlide === 1 ? 0 : currentSlide === 0 ? 100 : -100}% + ${dragOffset}px))`,
                            visibility: currentSlide === 1 ? 'visible' : 'hidden'
                        } : {}">
                        <div class="flex flex-col items-center">
                            <div
                                class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] w-full">
                                <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                    class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                <img src="{{ asset('images/marketing/reservation-2.png') }}" alt="reservation-picture"
                                    class="relative z-[1] h-full w-auto object-contain py-4">
                            </div>
                            <div class="my-6 box_grid-numering">
                                <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left" class="w-auto">
                                <div class="box_grid-numering_text">2</div>
                                <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                    class="w-auto">
                            </div>
                            <div class="px-4 pb-6 box_grid-info md:px-6">
                                <p class="mb-1 box_grid-title">Restaurant Notification</p>
                                <p class="box_grid-description">
                                    Once a reservation is successfully booked through PRIMA, we will notify the
                                    restaurant
                                    and ensure the diner is granted access in a seamless way.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute w-full transition-all duration-300 ease-out box_grid-item md:relative"
                        :class="{ 'opacity-0': isMobile && currentSlide !== 2 }"
                        :style="isMobile ? {
                            transform: `translateX(calc(${currentSlide === 2 ? 0 : currentSlide === 1 ? 100 : -100}% + ${dragOffset}px))`,
                            visibility: currentSlide === 2 ? 'visible' : 'hidden'
                        } : {}">
                        <div class="flex flex-col items-center">
                            <div
                                class="flex flex-col items-center justify-center relative overflow-hidden h-[240px] w-full">
                                <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                    class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                <img src="{{ asset('images/marketing/analytics-2.png') }}" alt="reservation-picture"
                                    class="relative z-[1] h-full w-auto object-contain py-4">
                            </div>
                            <div class="my-6 box_grid-numering">
                                <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                    class="w-auto">
                                <div class="box_grid-numering_text">3</div>
                                <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                    class="w-auto">
                            </div>
                            <div class="px-4 pb-6 box_grid-info md:px-6">
                                <p class="mb-1 box_grid-title">Live Analytics</p>
                                <p class="box_grid-description">
                                    Track real-time reservations, earnings, and performance through the PRIMA platform,
                                    giving you full transparency and control over your bookings.
                                </p>
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
                        <button @click="() => currentSlide = 1"
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
