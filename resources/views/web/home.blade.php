<x-layouts.web>

    <section class="fade-in">
        <div class="container ">
            <div class="intro">

                <!-- intro hero -->
                <div class="intro_hero">
                    <div class="intro_hero-img_hero">
                        <img src="{{ asset('/images/marketing/image-1.png') }}" alt=""
                            class="intro_hero-img_trans">
                    </div>
                    <div class="intro_hero-text">
                        <div class="intro_hero-text_container">
                            <h1 class="intro_hero-h1">
                                <span class="text-intro">Unlock Prime Dining</span>
                                <span class="text-intro">Reservations at the World’s Most</span>
                                <span class="text-intro">Sought-After Restaurants.</span>
                            </h1>
                        </div>
                    </div>
                    <div class="intro_hero-img_container">
                        <div class="intro_hero-img">
                            <img src="{{ asset('/images/marketing/city.png') }}" alt=""
                                class="hidden intro_hero-img_trans lg:block">
                            <img src="{{ asset('/images/marketing/casadona.jpg') }}" alt=""
                                class="block intro_hero-img_trans lg:hidden">
                        </div>
                        <div class="intro_hero-img">
                            <img src="{{ asset('/images/marketing/restaurant.png') }}" alt=""
                                class="hidden intro_hero-img_trans lg:block">
                            <img src="{{ asset('/images/marketing/sparow.jpg') }}" alt=""
                                class="block intro_hero-img_trans lg:hidden">
                        </div>
                    </div>
                </div>

                <x-venue-logos-scroll />

                <!-- cta -->
                <div class="intro_cta">
                    <div class="intro_cta-box bg-gradient-to-b from-blue to-green">
                        <div class="intro_cta-box_container">
                            <div class="intro_cta-box_text">
                                <h3 class="w-full">
                                    Concierges
                                </h3>
                                <p class="w-full text-tiny">
                                    Provide clients with priority reservations and elevate your service.
                                </p>
                            </div>

                            <a href="{{ route('concierges') }}" class="intro_cta-box_btn">
                                PRIMA for Concierges
                            </a>
                        </div>
                    </div>
                    <div class="intro_cta-box bg-gradient-to-b from-salmon to-salmon_accent">
                        <div class="intro_cta-box_container">
                            <div class="intro_cta-box_text">
                                <h3 class="w-full">
                                    Restaurants
                                </h3>
                                <p class="w-full text-tiny">
                                    Maximize profits, reduce cancellations, fill non-prime time reservations.
                                </p>
                            </div>
                            <a href="{{ route('restaurants') }}" class="intro_cta-box_btn">
                                PRIMA for Restaurants
                            </a>
                        </div>
                    </div>
                    <div class="intro_cta-box bg-gradient-to-b from-purple to-primary">
                        <div class="intro_cta-box_container">
                            <div class="intro_cta-box_text">
                                <h3 class="w-full">
                                    Consumers
                                </h3>
                                <p class="w-full text-tiny">
                                    Get guaranteed access to exclusive venues in Miami, New York, LA, and more.
                                </p>
                            </div>
                            <a href="#" class="intro_cta-box_btn"
                                @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                                Talk to PRIMA Concierge
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- VIP Access -->
                <div class="flex gap-4 md:gap-[40px] flex-col md:flex-row">
                    <div class="flex-1">
                        <img src="{{ asset('/images/marketing/consumers.png') }}" alt="app-image"
                            class="img_full rounded-[20px]">
                    </div>
                    <div class="flex flex-col items-start flex-1 gap-4">
                        <span class="gap-2 pl-2 tag">
                            <img src="{{ asset('/images/marketing/consumers_icon.svg') }}" alt="consumers-icon">
                            Consumers
                        </span>
                        <h2 class="text-dark">
                            Your VIP Access to the Hottest Venues
                        </h2>
                        <p>
                            Securing prime-time reservations in top cities like Miami, New York, and LA can be a
                            challenge. With PRIMA, you can skip the wait and enjoy immediate access to the hottest
                            venues.
                        </p>
                        <ul class="flex flex-col items-start flex-1 gap-4">
                            <li class="px-2 py-1.5 tag">
                                <img src="{{ asset('/images/marketing/check-salmon_icon.svg') }}" alt="check-icon">
                                Experience hassle-free dining without long waits.
                            </li>
                            <li class="px-2 py-1.5 tag">
                                <img src="{{ asset('/images/marketing/check-green_icon.svg') }}" alt="check-icon">
                                Book real-time reservations at your favorite venues.
                            </li>
                            <li class="px-2 py-1.5 tag">
                                <img src="{{ asset('/images/marketing/check-purple_icon.svg') }}" alt="check-icon">
                                Enjoy VIP access to exclusive dining experiences.
                            </li>
                        </ul>
                        <a href="#" class="text-white btn bg-gradient-to-b from-primary to-primary_light btn_full"
                            @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            Talk to PRIMA Concierge
                        </a>
                    </div>
                </div>

                <!-- VIP Access details -->
                <div class="flex gap-[16px] flex-col md:flex-row">
                    <div class="box_outline">
                        <p class="box_outline-title text-[16px] md:text-[18px]">
                            <img src="{{ asset('/images/marketing/calendar_icon.svg') }}" alt="calendar-icon"
                                width="24">
                            Select Your City and Check Availability
                        </p>
                        <p class="text-tiny">
                            Browse through exclusive restaurants and find your perfect spot.
                        </p>
                    </div>
                    <div class="box_outline">
                        <p class="box_outline-title">
                            <img src="{{ asset('/images/marketing/schedule_icon.svg') }}" alt="schedule-icon"
                                width="24">
                            Book Your Table Instantly with Confidence
                        </p>
                        <p class="text-tiny">
                            Enjoy the convenience of real-time reservations at your favorite venues.

                        </p>
                    </div>
                    <div class="box_outline">
                        <p class="box_outline-title">
                            <img src="{{ asset('/images/marketing/food_icon.svg') }}" alt="food-icon" width="24">
                            Experience Dining Without the Wait
                        </p>
                        <p class="text-tiny">
                            Experience dining without the hassle of long waits or last-minute cancellations.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container">
            <!-- Restaurants -->
            <div class="flex gap-[24px] md:gap-[40px] flex-col lg:flex-row">
                <div class="flex-1">
                    <div class="sticky top-[110px] flex flex-col gap-4 items-start">
                        <span class="gap-2 pl-2 tag">
                            <img src="{{ asset('/images/marketing/cutlery_icon.svg') }}" alt="cutlery-icon">
                            Restaurants
                        </span>
                        <h2 class="text-dark pr-[2px]">
                            Increase Profits, Reduce Cancellations, and Fill Non-Prime Time Reservations
                        </h2>
                        <p class="mb-[10px]">
                            PRIMA Increases Restaurant Profitability While Reducing Bots, Fake Reservations, and
                            Cancellations.
                        </p>
                        <a href="#"
                            class="text-white btn bg-gradient-to-b from-primary to-primary_light btn_full"
                            @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            Talk to PRIMA
                        </a>
                        <div class="bg-gradient-to-b from-salmon to-salmon_accent w-full box p-0 mt-[24px] ">
                            <div
                                class="pr-8 pl-8 justify-center flex pt-[130px] lg:pt-[50px] bg_cove bg_particle relative">
                                <img src="{{ asset('/images/marketing/particles.png') }}" alt="particles"
                                    class="absolute top-0 left-0 img_full">
                                <img src="{{ asset('/images/marketing/phone-3.png') }}" alt="phone"
                                    class="z-[1]">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- cards -->
                <div class="flex flex-col flex-1 gap-4">
                    <div class="box_md md:sticky top-[110px]">
                        <div class="box_md-header">
                            <div class="btn_icon btn bg-gradient-to-r from-pink to-pink_accent rounded-[12px]">
                                <img src="{{ asset('/images/marketing/star_icon.svg') }}" alt="star-icon">
                            </div>
                            <p class="box_md-title">
                                Sell Prime-Time Reservations
                            </p>
                        </div>
                        <p>
                            Offer your prime-time dining slots directly to committed diners through PRIMA and retain
                            60% of the booking fees. This ensures that your peak hours are filled with reliable guests,
                            boosting revenue while reducing cancellations.
                        </p>
                    </div>
                    <div class="box_md md:sticky top-[125px]">
                        <div class="box_md-header">
                            <div class="btn_icon btn bg-gradient-to-l from-primary to-blue_light rounded-[12px]">
                                <img src="{{ asset('/images/marketing/chairs_icon.svg') }}" alt="chairs-icon">
                            </div>
                            <p class="box_md-title">
                                Fill Non-Prime Time Reservations
                            </p>
                        </div>
                        <p>
                            Offer your prime-time dining slots directly to committed diners through PRIMA and retain
                            60% of the booking fees. This ensures that your peak hours are filled with reliable guests,
                            boosting revenue while reducing cancellations.
                        </p>
                    </div>
                    <div class="box_md md:sticky top-[140px]">
                        <div class="box_md-header">
                            <div class="btn_icon btn bg-gradient-to-tl from-cyan to-blue_dark rounded-[12px]">
                                <img src="{{ asset('/images/marketing/money_icon.svg') }}" alt="money-icon">
                            </div>
                            <p class="box_md-title">
                                Concierge Network Incentive
                            </p>
                        </div>
                        <p>
                            Offer your prime-time dining slots directly to committed diners through PRIMA and retain
                            60% of the booking fees. This ensures that your peak hours are filled with reliable guests,
                            boosting revenue while reducing cancellations.
                        </p>
                    </div>
                    <div class="box_md md:sticky top-[155px]">
                        <div class="box_md-header">
                            <div class="btn_icon btn bg-gradient-to-r from-blue_accent to-pink_strong rounded-[12px]">
                                <img src="{{ asset('/images/marketing/robot_icon.svg') }}" alt="robot-icon">
                            </div>
                            <p class="box_md-title">
                                Eliminate Bots, Cancellations, and Overbooking
                            </p>
                        </div>
                        <p>
                            Offer your prime-time dining slots directly to committed diners through PRIMA and retain
                            60% of the booking fees. This ensures that your peak hours are filled with reliable guests,
                            boosting revenue while reducing cancellations.
                        </p>
                    </div>
                    <div class="box_md md:sticky top-[175px]">
                        <div class="box_md-header">
                            <div class="btn_icon btn bg-gradient-to-r from-purple_light to-purple_dark rounded-[12px]">
                                <img src="{{ asset('/images/marketing/reservation_icon.svg') }}"
                                    alt="reservation-icon">
                            </div>
                            <p class="box_md-title">
                                Real-Time Reservation Management
                            </p>
                        </div>
                        <p>
                            Offer your prime-time dining slots directly to committed diners through PRIMA and retain
                            60% of the booking fees. This ensures that your peak hours are filled with reliable guests,
                            boosting revenue while reducing cancellations.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in md:mt-[80px] md:p-[16px]">
        <div class="container">
            <!-- app -->
            <div class="flex gap-4 md:gap-[40px] flex-col lg:flex-row">
                <div class="flex-1">
                    <div class="bg-gradient-to-b from-blue to-green w-full box p-0 sticky top-[110px]">
                        <div class="pr-8 pl-8 justify-center flex pt-[20px] md:pt-14 bg_cover bg_particle relative">
                            <img src="{{ asset('images/marketing/particles.png') }}" alt="particles"
                                class="absolute top-0 left-0 img_full">
                            <img src="{{ asset('images/marketing/phone-5.png') }}" alt="phone"
                                class="z-[1] max-h-[520px]">
                        </div>
                    </div>
                </div>

                <!-- Concierges + cards -->
                <div class="flex-1 flex flex-col gap-4 items-start md:px-[12px]">
                    <div class="flex flex-col items-start gap-4">
                        <span class="gap-2 pl-2 tag">
                            <img src="{{ asset('images/marketing/group_icon.svg') }}" alt="group-icon">
                            Concierges
                        </span>
                        <h2 class="text-dark tracking-[-0.03em] md:tracking-normal">
                            Provide VIP Access and Enhance Your Service
                        </h2>
                        <p class="mb-4">
                            Unlock exclusive dining experiences for your clients with priority reservations at top
                            restaurants. Elevate your service by catering to affluent customers seeking unique culinary
                            adventures.
                        </p>
                    </div>
                    <div class="grid w-full grid-cols-1 gap-4 xl:grid-cols-2">
                        <div class="card_lg">
                            <div class="flex items-start justify-start gap-4">
                                <div class="btn_icon btn bg-gradient-to-tl from-cyan to-blue_dark">
                                    <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip_icon"
                                        width="30">
                                </div>
                                <div class="flex-1">
                                    <p class="card_lg-title">
                                        <span class="btn_icon btn bg-gradient-to-tl from-cyan to-blue_dark">
                                            <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip_icon"
                                                width="30">
                                        </span>
                                        Concierge Services
                                    </p>
                                    <p class="card_lg-text">
                                        Offer VIP reservations as a seamless part of your service.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card_lg">
                            <div class="flex items-start justify-start gap-4">
                                <div class="btn_icon btn bg-gradient-to-r from-pink to-pink_accent">
                                    <img src="{{ asset('images/marketing/money_icon.svg') }}" alt="money_icon">
                                </div>
                                <div class="flex-1">
                                    <p class="card_lg-title">
                                        <span class="btn_icon btn bg-gradient-to-r from-pink to-pink_accent">
                                            <img src="{{ asset('images/marketing/money_icon.svg') }}"
                                                alt="money_icon">
                                        </span>
                                        Earn Commissions
                                    </p>
                                    <p class="card_lg-text">
                                        Earn up to 15% commission on premium reservation bookings.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card_lg">
                            <div class="flex items-start justify-start gap-4">
                                <div class="btn_icon btn bg-gradient-to-r from-purple_light to-purple_dark">
                                    <img src="{{ asset('images/marketing/date_icon.svg') }}" alt="money-icon"
                                        width="30">
                                </div>
                                <div class="flex-1">
                                    <p class="card_lg-title">
                                        <span class="btn_icon btn bg-gradient-to-r from-purple_light to-purple_dark">
                                            <img src="{{ asset('images/marketing/date_icon.svg') }}" alt="money-icon"
                                                width="30">
                                        </span>
                                        Exclusive Access
                                    </p>
                                    <p class="card_lg-text">
                                        Provide clients with access to the finest dining experiences available.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card_lg">
                            <div class="flex items-start justify-start gap-4">
                                <div class="btn_icon btn bg-gradient-to-r from-blue_accent to-pink_strong">
                                    <img src="{{ asset('images/marketing/champ_icon.svg') }}" alt="robot-icon"
                                        width="30">
                                </div>
                                <div class="flex-1">
                                    <p class="card_lg-title">
                                        <span class="btn_icon btn bg-gradient-to-r from-blue_accent to-pink_strong">
                                            <img src="{{ asset('images/marketing/champ_icon.svg') }}"
                                                alt="robot-icon" width="30">
                                        </span>
                                        Enhanced Prestige
                                    </p>
                                    <p class="card_lg-text">
                                        Deliver premium service and gain recognition in the industry.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="#"
                        class="flex-auto mt-4 text-white btn bg-gradient-to-b from-primary to-primary_light btn_full"
                        @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                        Talk to PRIMA
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container-small">
            <div class="flex flex-col items-start gap-4 md:text-center md:items-center">
                <span class="gap-2 pl-2 tag">
                    <img src="{{ asset('images/marketing/play_icon.svg') }}" alt="play-icon">
                    How It Works
                </span>
                <h2 class="text-dark">
                    Watch Our Explainer Video
                </h2>
                <p class="mb-4">
                    With PRIMA, you can effortlessly browse available dining options and secure your reservations in
                    real-time. Enjoy guaranteed bookings without the usual hassle of waiting lists.
                </p>
                <iframe src="https://www.youtube.com/embed/pxyHz-RjHW0?si=ez97HMpU4Bf2-LCp"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </section>

    <section class="scroll-section mt-[30px] md:mt-[60px]">
        <div class="container">
            <div class="gallery">
                <div class="gallery_bg">
                    <img src="{{ asset('images/marketing/bg_pulse.png') }}" alt="pulse-background"
                        class="gallery_bg-img">
                </div>
                <div class="gallery_container">
                    <img src="{{ asset('images/marketing/smile_icon.svg') }}" alt="smile-icon"
                        class="inline-block ml-10">
                    <h2 class="mt-2 text-center text-white text-gigant">
                        With PRIMA
                        <span class="block text-yellow">
                            Everybody Wins​
                        </span>
                    </h2>
                    <div class="gallery_item-tc gallery_item">
                        <img src="{{ asset('images/marketing/everyone-2.png') }}" alt="people-dinner"
                            class="gallery_item-image">
                    </div>
                    <div class="gallery_item-tl gallery_item">
                        <img src="{{ asset('images/marketing/everyone-1.png') }}" alt="chef"
                            class="gallery_item-image">
                    </div>
                    <div class="gallery_item-tr gallery_item">
                        <img src="{{ asset('images/marketing/everyone-3.png') }}" alt="woman-eating"
                            class="gallery_item-image">
                    </div>
                    <div class="gallery_item-bl gallery_item">
                        <img src="{{ asset('images/marketing/everyone-4.png') }}" alt="woman-eating"
                            class="gallery_item-image">
                    </div>
                    <div class="gallery_item-br gallery_item">
                        <img src="{{ asset('images/marketing/everyone-5.png') }}" alt="chef"
                            class="gallery_item-image">
                    </div>
                </div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </section>

</x-layouts.web>
