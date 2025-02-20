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
            this.containerHeight = `${currentItem.scrollHeight + 20}px`;
        }
    },

    next() {
        this.currentSlide = (this.currentSlide + 1) % 3;
        this.dragOffset = 0;
        this.updateHeight();
    },
    prev() {
        this.currentSlide = (this.currentSlide - 1 + 3) % 3;
        this.dragOffset = 0;
        this.updateHeight();
    },
    handleTouchStart(e) {
        if (!this.isMobile) return;
        if (!e.target.closest('.box_grid')) return;

        this.isSwiping = true;
        this.touchStartX = e.touches[0].clientX;
        e.preventDefault();
    },
    handleTouchMove(e) {
        if (!this.isSwiping) return;

        const currentX = e.touches[0].clientX;
        this.dragOffset = currentX - this.touchStartX;
        e.preventDefault();
    },
    handleTouchEnd(e) {
        if (!this.isSwiping) return;

        this.touchEndX = e.changedTouches[0].clientX;
        const diff = this.touchStartX - this.touchEndX;

        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                this.next();
            } else {
                this.prev();
            }
        }

        this.dragOffset = 0;
        this.isSwiping = false;
        e.stopPropagation();
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
                <!-- Outer container to maintain proper width -->
                <div class="relative overflow-hidden">
                    <!-- Slides Container -->
                    <div class="relative md:grid md:grid-cols-3 md:gap-4"
                        :style="isMobile ? { height: containerHeight } : {}"
                        @touchstart.prevent="handleTouchStart($event)" @touchend.prevent="handleTouchEnd($event)"
                        @touchmove.prevent="handleTouchMove($event)">

                        <div class="absolute inset-0 w-full transition-all duration-300 ease-out box_grid-item md:relative md:inset-auto"
                            :class="{ 'opacity-0': isMobile && currentSlide !== 0 }"
                            :style="isMobile ? {
                                transform: `translateX(${currentSlide === 0 ? dragOffset : currentSlide === 2 ? 100 : -100}%)`,
                                visibility: currentSlide === 0 ? 'visible' : 'hidden'
                            } : {}">
                            <div class="flex flex-col items-center">
                                <div class="flex flex-col items-center relative overflow-hidden max-h-[300px]">
                                    <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                        class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                    <img src="{{ asset('images/marketing/reservation-1.png') }}"
                                        alt="reservation-picture" class="relative z-[1] max-h-[100%] h-[auto]">
                                </div>
                                <div class="flex items-center justify-center box_grid-numering">
                                    <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                        class="w-auto">
                                    <div class="box_grid-numering_text">1</div>
                                    <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                        class="w-auto">
                                </div>
                                <div class="flex flex-col items-center box_grid-info md:items-start">
                                    <p class="box_grid-title">Book Reservation</p>
                                    <p class="text-center box_grid-description md:text-left">
                                        Concierges use the PRIMA Hub to book prime-time reservations for a fee.
                                        Non-prime slots are
                                        booked without fees.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute inset-0 w-full transition-all duration-300 ease-out box_grid-item md:relative md:inset-auto"
                            :class="{ 'opacity-0': isMobile && currentSlide !== 1 }"
                            :style="isMobile ? {
                                transform: `translateX(${currentSlide === 1 ? dragOffset : currentSlide === 0 ? 100 : -100}%)`,
                                visibility: currentSlide === 1 ? 'visible' : 'hidden'
                            } : {}">
                            <div class="flex flex-col items-center">
                                <div class="flex flex-col items-center relative overflow-hidden max-h-[300px]">
                                    <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                        class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                    <img src="{{ asset('images/marketing/reservation-2.png') }}"
                                        alt="reservation-picture" class="relative z-[1] max-h-[100%] h-[auto]">
                                </div>
                                <div class="flex items-center justify-center box_grid-numering">
                                    <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                        class="w-auto">
                                    <div class="box_grid-numering_text">2</div>
                                    <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                        class="w-auto">
                                </div>
                                <div class="flex flex-col items-center box_grid-info md:items-start">
                                    <p class="box_grid-title">Restaurant Notification</p>
                                    <p class="text-center box_grid-description md:text-left">
                                        Once a reservation is successfully booked through PRIMA, we will notify the
                                        restaurant
                                        and
                                        ensure the diner is granted access in a seamless way.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute inset-0 w-full transition-all duration-300 ease-out box_grid-item md:relative md:inset-auto"
                            :class="{ 'opacity-0': isMobile && currentSlide !== 2 }"
                            :style="isMobile ? {
                                transform: `translateX(${currentSlide === 2 ? dragOffset : currentSlide === 1 ? 100 : -100}%)`,
                                visibility: currentSlide === 2 ? 'visible' : 'hidden'
                            } : {}">
                            <div class="flex flex-col items-center">
                                <div class="flex flex-col items-center relative overflow-hidden max-h-[300px]">
                                    <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                                        class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                                    <img src="{{ asset('images/marketing/analytics-2.png') }}"
                                        alt="reservation-picture" class="relative z-[1] max-h-[100%] h-[auto]">
                                </div>
                                <div class="flex items-center justify-center box_grid-numering">
                                    <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left"
                                        class="w-auto">
                                    <div class="box_grid-numering_text">3</div>
                                    <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right"
                                        class="w-auto">
                                </div>
                                <div class="flex flex-col items-center box_grid-info md:items-start">
                                    <p class="box_grid-title">Live Analytics</p>
                                    <p class="text-center box_grid-description md:text-left">
                                        Track real-time reservations, earnings, and performance through the PRIMA
                                        platform,
                                        giving
                                        you full transparency and control over your bookings.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation controls -->
                <div class="flex items-center justify-between w-full mt-6 md:hidden">
                    <button @click="prev" class="p-2 text-gray-600 transition-colors hover:text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="flex gap-2">
                        <div :class="{ 'bg-primary': currentSlide === 0, 'bg-gray-300': currentSlide !== 0 }"
                            class="w-2 h-2 transition-colors rounded-full"></div>
                        <div :class="{ 'bg-primary': currentSlide === 1, 'bg-gray-300': currentSlide !== 1 }"
                            class="w-2 h-2 transition-colors rounded-full"></div>
                        <div :class="{ 'bg-primary': currentSlide === 2, 'bg-gray-300': currentSlide !== 2 }"
                            class="w-2 h-2 transition-colors rounded-full"></div>
                    </div>
                    <button @click="next" class="p-2 text-gray-600 transition-colors hover:text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
