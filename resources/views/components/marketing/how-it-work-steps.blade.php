@props([
    'title' => 'Effortless Reservation Management with PRIMA',
    'description' =>
        'Maximize your revenue by filling prime-time slots and reducing no-shows. PRIMA connects your restaurant with trusted concierges, ensuring reliable bookings and high-value clientele.',
        'steps' => [
        [
            'image' => 'images/marketing/reservation-1.png',
            'title' => 'Book Reservation',
            'description' => 'Concierges use the PRIMA Hub to book prime-time reservations for a fee. Non-prime slots are booked without fees.',
        ],
        [
            'image' => 'images/marketing/reservation-2.png',
            'title' => 'Restaurant Notification',
            'description' => 'Once a reservation is successfully booked through PRIMA, we will notify the restaurant and ensure the diner is granted access in a seamless way.',
        ],
        [
            'image' => 'images/marketing/analytics-2.png',
            'title' => 'Live Analytics',
            'description' => 'Track real-time reservations, earnings, and performance through the PRIMA platform, giving you full transparency and control over your bookings.',
        ],
    ],
])

<section class="fade-in mt-[60px] md:mt-[80px]" x-data="howItWorks({{ count($steps) }})">
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

                    @foreach ($steps as $index => $step)
                        <x-marketing.step :step="$step" :index="$index" />
                    @endforeach

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
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('howItWorks', (stepsCount) => ({
            currentSlide: 0,
            dragOffset: 0,
            touchStartX: 0,
            isMobile: window.innerWidth < 768,

            init() {
                window.addEventListener('resize', () => {
                    const wasMobile = this.isMobile;
                    this.isMobile = window.innerWidth < 768;

                    if (wasMobile !== this.isMobile) {
                        this.currentSlide = 0;
                        this.dragOffset = 0;
                        this.touchStartX = null;
                    }
                });
            },

            next() {
                if (!this.isMobile) return;
                if (this.currentSlide < stepsCount - 1) this.currentSlide++;
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
        }));
    });
</script>
