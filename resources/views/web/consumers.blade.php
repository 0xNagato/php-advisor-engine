@php
    use App\Models\VipCode;
    $vipCode = 'PRIMA';
@endphp
<x-layouts.web>

    <section class="fade-in">
        <div class="container">
            <div class="intro">

                <!-- intro hero -->
                <div class="intro_hero">
                    <div class="intro_hero-img_hero">
                        <img src="{{ asset('images/marketing/image-1.png') }}" alt="" class="intro_hero-img_trans">
                    </div>
                    <div class="intro_hero-text">
                        <div class="intro_hero-text_container md:py-[35px]">
                            <h1 class="intro_hero-h1 max-w-[360px] md:max-w-[560px] lg:max-w-none">
                                <span class="text-intro">Experience Premium Dining</span>
                                <span class="text-intro">Anytime, Anywhere</span>
                            </h1>
                            <span class="intro_hero-btn_container">
                                <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode], false), '/') }}"
                                    class="intro_hero-btn bg-gradient-to-b from-primary to-purple">
                                    Book Your Dining Experience
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="intro_hero-img_container">
                        <div class="intro_hero-img">
                            <img src="{{ asset('images/marketing/city.png') }}" alt=""
                                class="intro_hero-img_trans">
                        </div>
                        <div class="intro_hero-img">
                            <img src="{{ asset('images/marketing/consumers.jpeg') }}" alt=""
                                class="intro_hero-img_trans">
                        </div>
                    </div>
                </div>

                <!-- mission vision -->
                <div class="relative flex flex-col gap-4 md:gap-6 lg:flex-row">
                    <div
                        class="flex flex-col items-start flex-1 gap-4 p-6 box bg-gradient-to-b from-secondary to-primary">
                        <span class="gap-2 pl-2 bg-white tag">
                            <img src="{{ asset('images/marketing/shield_icon.svg') }}" alt="shield-icon">
                            Our Mission
                        </span>
                        <p class="antialiased font-semibold tracking-normal text-white font-bricolage text-xl_1">
                            To empower diners by offering access to exclusive, premium dining reservations while
                            enhancing the dining experience through seamless booking processes.
                        </p>
                    </div>
                    <div
                        class="flex flex-col items-start flex-1 gap-4 p-6 box bg-gradient-to-b from-green_dark to-green">
                        <span class="gap-2 pl-2 bg-white tag">
                            <img src="{{ asset('images/marketing/eye_icon.svg') }}" alt="eye-icon">
                            Our Vision
                        </span>
                        <p class="antialiased font-semibold tracking-normal text-white font-bricolage text-xl_1">
                            To create a world where every dining reservation feels like a VIP experience, offering
                            consumers effortless access to their favorite restaurants and exceptional customer service.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in mt-[60px] md:mt-[70px] lg:mt-[100px]">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- The Problem -->
                <div class="flex gap-4 md:gap-[32px] flex-col lg:flex-row">
                    <div class="flex-1 lg:max-w-[537px] mb-[25px] lg:mb-[0px]">
                        <img src="{{ asset('images/marketing/consumers-1.png') }}" alt="app-image"
                            class="img_full rounded-[20px]">
                    </div>
                    <div class="flex flex-col items-start flex-1 gap-4 ">
                        <span class="tag gap-2 pl-2 font-[600]">
                            <img src="{{ asset('images/marketing/stats_icon.svg') }}" alt="stats_icon">
                            The Problem
                        </span>
                        <h2 class="text-dark">
                            Challenges Faced by Diners
                        </h2>
                        <p>
                            Accessing prime-time reservations at the best restaurants can be frustrating and
                            time-consuming. Here are some common problems diners face:
                        </p>
                        <div class="flex flex-col gap-[9px]">
                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/date_time.svg') }}" alt="date_time"
                                            width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/date_time.svg') }}" alt="date_time"
                                                    width="30">
                                            </span>
                                            Long Waitlists​
                                        </p>
                                        <p class="card_lg-text">
                                            Getting a table at popular restaurants often means waiting weeks or even
                                            months.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/cancel_icon.svg') }}" alt="cancel-icon"
                                            width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/cancel_icon.svg') }}"
                                                    alt="cancel-icon" width="30">
                                            </span>
                                            Third-Party Reservation Hoarding
                                        </p>
                                        <p class="card_lg-text">
                                            Many prime-time spots are resold by brokers, leading to inflated prices and
                                            fewer genuine opportunities for diners.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/cutlery_icon--outline.svg') }}"
                                            alt="cutlery-icon" width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/cutlery_icon--outline.svg') }}"
                                                    alt="cutlery-icon" width="30">
                                            </span>
                                            Unreliable Reservation Systems
                                        </p>
                                        <p class="card_lg-text">
                                            Last-minute cancellations and fake bookings create uncertainty for diners.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip-icon"
                                            width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip-icon"
                                                    width="30">
                                            </span>
                                            Lack of VIP Access
                                        </p>
                                        <p class="card_lg-text">
                                            High-value diners struggle to secure reservations at exclusive venues
                                            without the right connections.
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- Consumers -->
                <div class="flex flex-col gap-4 md:gap-10 md:flex-row">
                    <div class="flex-1">
                        <div class="sticky top-[110px]">
                            <img src="{{ asset('images/marketing/vip-access.png') }}" alt="app-image">
                        </div>
                    </div>
                    <div class="flex flex-col items-start flex-1 gap-4">
                        <span class="tag gap-2 pl-2 font-[600] text-dark font-instrument">
                            <img src="{{ asset('images/marketing/cutlery_icon.svg') }}" alt="cutlery-icon">
                            Welcome to PRIMA
                        </span>
                        <h2 class="text-dark">
                            Your VIP Access to the Best Dining Reservations
                        </h2>
                        <p>
                            PRIMA is the first platform designed to help in-demand restaurants maximize income by
                            directly selling prime-time reservations through a trusted network of partners. PRIMA also
                            increases non-prime time bookings, reducing last-minute cancellations and eliminating fake
                            reservations.
                        </p>
                        <ul class="list_colored mt-[0px]">
                            <li class="tag pl-2 leading-[1.5] font-[500] font-instrument">
                                <img src="{{ asset('images/marketing/check-salmon_icon.svg') }}" alt="check-icon">
                                Skip the waitlists and secure your spot at the most in-demand restaurants.
                            </li>
                            <li class="tag pl-2 leading-[1.5] font-[500] font-instrument">
                                <img src="{{ asset('images/marketing/check-green_icon.svg') }}" alt="check-icon">
                                Avoid the frustration of fake reservations or last-minute cancellations.
                            </li>
                            <li class="tag pl-2 leading-[1.5] font-[500] font-instrument">
                                <img src="{{ asset('images/marketing/check-purple_icon.svg') }}" alt="check-icon">
                                Enjoy VIP treatment at top-tier dining spots worldwide.
                            </li>
                        </ul>
                        <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode], false), '/') }}"
                            class="w-full text-center text-white btn bg-gradient-to-b from-purple to-primary md:w-auto">
                            Book Your Dream Dining Experience
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <x-marketing.how-it-work-steps :steps="[
        [
            'image' => 'images/marketing/reservation-1.png',
            'title' => 'Browse & Select',
            'description' => 'Select from exclusive prime-time slots and book your favorite restaurants in just a few clicks.',
        ],
        [
            'image' => 'images/marketing/reservation-2.png',
            'title' => 'Book Your Reservation',
            'description' => 'Receive immediate confirmation for your reservation, guaranteeing your table without the wait.',
        ],
        [
            'image' => 'images/marketing/fine-dining.png',
            'title' => 'Enjoy Your Experience',
            'description' => 'Gain entry to highly sought-after dining experiences, often unavailable through traditional channels.',
        ],
    ]">
        <x-slot:title>Easy Access to Exclusive Dining Experiences with PRIMA</x-slot:title>
        <x-slot:description>
            Enjoy seamless access to exclusive reservations at the world’s top restaurants.
            Skip the waitlists and secure prime dining experiences through PRIMA’s trusted concierge network.
        </x-slot:description>
    </x-marketing.how-it-work-steps>

    <section class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- Consumers -->
                <div class="flex gap-[16px] lg:gap-10 flex-col lg:flex-row">
                    <div class="flex-1">
                        <img src="{{ asset('images/marketing/access-exclusive-dinning.png') }}"
                            alt="access-exclusive-dinning" class="img_full rounded-[20px]">
                    </div>
                    <div class="flex flex-col items-start justify-center flex-1 gap-4 lg:py-8">
                        <span class="tag gap-2 pl-2 font-[600] text-dark font-instrument">
                            <img src="{{ asset('images/marketing/person_icon.svg') }}" alt="person-icon">
                            Consumers
                        </span>
                        <h2 class="text-dark">
                            Access Exclusive Dining Through Our Trusted Network
                        </h2>
                        <p>
                            All PRIMA reservations are secured by expert concierges from prestigious hotels and luxury
                            communities in major cities. This guarantees you a seamless dining experience at top
                            restaurants, with reliable bookings and fewer cancellations.
                        </p>
                        <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode], false), '/') }}"
                            class="btn bg-gradient-to-b from-purple to-primary text-white mt-[10px] md:mt-6 btn_full">
                            Book Your Dining Experience
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="fade-in lg:mt-[80px] mt-[60px]">
        <div class="container">
            <div class="flex flex-col items-center gap-8">
                <div class="flex flex-col gap-[12px] items-start md:items-center md:text-center">
                    <span class="tag gap-2 pl-2 font-[600] text-dark font-instrument">
                        <img src="{{ asset('images/marketing/money_plus_icon.svg') }}" alt="money-icon">
                        Why PRIMA
                    </span>
                    <h2 class="text-dark md:m-auto mb-[0]">
                        Why Choose PRIMA?
                    </h2>
                    <p class="max-w-[924px] m-auto">
                        Elevate your dining experience with exclusive access to top restaurants worldwide, ensuring your
                        reservations are always secured.
                    </p>
                </div>

                <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode], false), '/') }}"
                    class="text-white btn bg-gradient-to-b from-purple to-primary btn_full">
                    Book Your Dining Experience
                </a>

                <div class="grid w-full grid-cols-1 gap-4 2 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div class="btn_icon btn bg-gradient-to-tl from-pink to-pink_accent rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip-icon"
                                    class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                VIP Access
                            </p>
                        </div>
                        <p class="text-tiny">
                            Get reservations at the most in-demand restaurants, even during peak hours.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div
                                class="btn_icon btn bg-gradient-to-l from-primary to-blue_light rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/schedule_icon--light.svg') }}"
                                    alt="schedule-icon" class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                Seamless Booking
                            </p>
                        </div>
                        <p class="text-tiny">
                            Enjoy a streamlined process that takes the hassle out of dining reservations.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div
                                class="btn_icon btn bg-gradient-to-r from-purple_light to-purple rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/group-premium_icon.svg') }}"
                                    alt="group-premium-icon" class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                Trusted Network
                            </p>
                        </div>
                        <p class="text-tiny">
                            Reliable platform that partners with premium concierges to deliver top-tier services.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div
                                class="btn_icon btn bg-gradient-to-l from-pink_strong to-blue_accent rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/food-services_icon.svg') }}"
                                    alt="food-services_icon-icon" class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                Handpicked Venues
                            </p>
                        </div>
                        <p class="text-tiny">
                            Only the best restaurants, chosen for their quality and dining experience.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div class="btn_icon btn bg-gradient-to-tl from-cyan to-primary rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/chairs_icon.svg') }}" alt="chair-icon"
                                    class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                Priority Reservations
                            </p>
                        </div>
                        <p class="text-tiny">
                            Never miss out on your favorite spots, even during busy times.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div class="btn_icon btn bg-gradient-to-b from-blue to-green rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="star-icon"
                                    class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                Unmatched Service
                            </p>
                        </div>
                        <p class="text-tiny">
                            Enjoy VIP treatment from the moment you book until your meal is complete.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container pt-0">
            <div class="box_cta bg-gradient-to-tl from-primary to-purple">
                <h3 class="text-center box_cta-title">
                    Ready to transform the way you dine?
                </h3>
                <p>
                    Join PRIMA and get exclusive access to the world’s top restaurants.
                </p>
                <ul class="tag_group">
                    <li class="tag tag--transparent">
                        <img src="{{ asset('images/marketing/check-salmon_icon.svg') }}" alt="check-icon">
                        Unlock VIP Dining
                    </li>
                    <li class="tag tag--transparent">
                        <img src="{{ asset('images/marketing/check-green_icon.svg') }}" alt="check-icon">
                        Simple, Secure Booking
                    </li>
                    <li class="tag tag--transparent">
                        <img src="{{ asset('images/marketing/check-purple_icon.svg') }}" alt="check-icon">
                        No More Waiting, Just Enjoy!
                    </li>
                </ul>
                <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode], false), '/') }}"
                    class="btn bg-white text-primary mt-4 relative z-10 btn_full font-bricolage font-[600] antialiased">
                    Reserve Your Table Now!
                </a>
                <div class="box_cta-deco">
                    <img src="{{ asset('images/marketing/background-left.png') }}" alt="background-left">
                    <img src="{{ asset('images/marketing/background-right.png') }}" alt="background-right">
                </div>
            </div>
        </div>
    </section>

</x-layouts.web>
