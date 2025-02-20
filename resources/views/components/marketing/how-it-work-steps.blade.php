@props([
    'title' => 'Effortless Reservation Management with PRIMA',
    'description' =>
        'Maximize your revenue by filling prime-time slots and reducing no-shows. PRIMA connects your restaurant with trusted concierges, ensuring reliable bookings and high-value clientele.',
])

<section class="fade-in mt-[60px] md:mt-[80px]">
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

            <div class="box_grid">
                <div class="box_grid-item">
                    <!-- picture -->
                    <div class="flex flex-col items-center relative overflow-hidden max-h-[300px] px-5">
                        <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                            class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                        <img src="{{ asset('images/marketing/reservation-1.png') }}" alt="reservation-picture"
                            class="relative z-[1] max-h-[100%] h-[auto]">
                    </div>
                    <!-- nav -->
                    <div class="box_grid-numering">
                        <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left" class="w-auto">

                        <div class="box_grid-numering_text">
                            1
                        </div>
                        <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right" class="w-auto">
                    </div>
                    <div class="box_grid-info">
                        <p class="box_grid-title">
                            Book Reservation
                        </p>
                        <p class="box_grid-description">
                            Concierges use the PRIMA Hub to book prime-time reservations for a fee. Non-prime slots are
                            booked without fees.
                        </p>
                    </div>
                </div>
                <div class="box_grid-item">
                    <!-- picture -->
                    <div class="flex flex-col items-center relative overflow-hidden max-h-[300px] px-5">
                        <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                            class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                        <img src="{{ asset('images/marketing/reservation-2.png') }}" alt="reservation-picture"
                            class="relative z-[1] max-h-[100%] h-[auto]">">
                    </div>
                    <!-- nav -->
                    <div class="box_grid-numering">
                        <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left" class="w-auto">

                        <div class="box_grid-numering_text">
                            2
                        </div>
                        <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right" class="w-auto">
                    </div>
                    <div class="box_grid-info">
                        <p class="box_grid-title">
                            Restaurant Notification
                        </p>
                        <p class="box_grid-description">
                            Once a reservation is successfully booked through PRIMA, we will notify the restaurant and
                            ensure the diner is granted access in a seamless way.
                        </p>
                    </div>
                </div>
                <div class="box_grid-item">
                    <!-- picture -->
                    <div class="flex flex-col items-center relative overflow-hidden max-h-[300px] px-5">
                        <img src="{{ asset('images/marketing/dots_bg.png') }}" alt="dots"
                            class="absolute top-0 left-0 right-0 m-auto max-w-[400px] w-full">
                        <img src="{{ asset('images/marketing/analytics-2.png') }}" alt="reservation-picture"
                            class="relative z-[1] max-h-[100%] h-[auto]">">
                    </div>
                    <!-- nav -->
                    <div class="box_grid-numering">
                        <img src="{{ asset('images/marketing/line-left.svg') }}" alt="line-left" class="w-auto">

                        <div class="box_grid-numering_text">
                            3
                        </div>
                        <img src="{{ asset('images/marketing/line-right.svg') }}" alt="line-right" class="w-auto">
                    </div>
                    <div class="box_grid-info">
                        <p class="box_grid-title">
                            Live Analytics
                        </p>
                        <p class="box_grid-description">
                            Track real-time reservations, earnings, and performance through the PRIMA platform, giving
                            you full transparency and control over your bookings.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
