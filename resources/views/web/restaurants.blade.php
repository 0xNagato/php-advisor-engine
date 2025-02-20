<x-layouts.web>

    <section class="fade-in">
        <div class="container">
            <div class="intro">

                <!-- intro hero -->
                <div class="intro_hero">
                    <div class="intro_hero-img_hero">
                        <img src="{{ asset('images/marketing/restaurant-1.png') }}" alt="restaurant"
                            class="img_full hover:scale-[1.01] transition_03 rounded-[20px]">
                    </div>
                    <div class="intro_hero-text">
                        <div class="intro_hero-text_container">
                            <h1 class="intro_hero-h1 max-w-[360px] md:max-w-[580px] lg:max-w-none">
                                <span class="text-intro">Transform Your Restaurant's Revenue</span>
                                <span class="text-intro">With PRIMA’s Reservation Solutions.
                                </span>
                            </h1>
                            <span class="intro_hero-btn_container mt-[10px] lg:mt-0">
                                <a href="#" class="intro_hero-btn bg-gradient-to-b from-salmon to-salmon_accent"
                                    @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                                    Talk To PRIMA
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 flex gap-4  md:gap-6 md:flex-col">
                        <div class="flex-1 h-[200px] lg:min-h-[248px]">
                            <img src="{{ asset('images/marketing/city.png') }}" alt=""
                                class="img_full hover:scale-[1.01] transition_03 rounded-[20px]">
                        </div>
                        <div class="flex-1 h-[200px] lg:min-h-[248px]">
                            <img src="{{ asset('images/marketing/restaurant-3.png') }}" alt=""
                                class="img_full hover:scale-[1.01] transition_03 rounded-[20px]">
                        </div>
                    </div>
                </div>

                <!-- mission vision -->
                <div class="intro_cta intro_cta--2_col gap-[24px]">
                    <div class="box_gradient bg-gradient-to-b from-purple_vintage to-blue_deep">
                        <span class="tag tag--strong pl-2 bg-white">
                            <img src="{{ asset('images/marketing/shield_icon.svg') }}" alt="shield-icon">
                            Consumers
                        </span>
                        <p class="box_gradient-text">
                            To empower restaurants by providing a seamless and innovative platform which maximizes
                            profits, eliminates reservation inefficiencies, and ensures reliable bookings through a
                            trusted concierge network.
                        </p>
                    </div>
                    <div class="box_gradient bg-gradient-to-b from-blue to-green">
                        <span class="tag tag--strong bg-white">
                            <img src="{{ asset('images/marketing/eye_icon.svg') }}" alt="eye-icon">
                            Our Vision
                        </span>
                        <p class="box_gradient-text">
                            To revolutionize the dining industry by creating a world where every reservation is a
                            premium experience, ensuring restaurants thrive while offering diners exclusive access to
                            the most sought-after venues.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in mt-[60px] md:mt-[70px] lg:mt-[80px]">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- The Problem -->
                <div class="flex gap-4 md:gap-[32px] flex-col lg:flex-row">
                    <div class="flex-1 lg:max-w-[537px] lg:mb-[0px]">
                        <img src="{{ asset('images/marketing/restaurant-problem.png') }}" alt="restaurant"
                            class="img_full rounded-[20px]">
                    </div>
                    <div class="flex-1 flex flex-col gap-4 items-start ">
                        <span class="tag gap-2 pl-2 font-[600]">
                            <img src="{{ asset('images/marketing/stats_icon.svg') }}" alt="cutlery-icon">
                            The Problem
                        </span>
                        <h2 class="text-dark">
                            The Challenges Facing <span class="sm:block">In-Demand Restaurants</span>
                        </h2>
                        <p>
                            In-demand restaurants face several hurdles that prevent them from maximizing profits and
                            ensuring reliable bookings.
                        </p>
                        <div class="flex flex-col gap-[9px]">
                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[10px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/cutlery_icon--outline.svg') }}"
                                            alt="cutlery-icon" width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal md:mb-0">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/cutlery_icon--outline.svg') }}"
                                                    alt="cutlery-icon" width="30">
                                            </span>
                                            Lost Revenue to Third Parties
                                        </p>
                                        <p class="card_lg-text">
                                            Unauthorized third parties resell valuable reservations, taking profits away
                                            from restaurants.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/unauthorized_icon.svg') }}"
                                            alt="cancel-icon" width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal md:mb-0">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/unauthorized_icon.svg') }}"
                                                    alt="cancel-icon" width="30">
                                            </span>
                                            No-Shows and Fake Reservations
                                        </p>
                                        <p class="card_lg-text">
                                            Bots and brokers book reservations with no intention of attending, leading
                                            to empty tables and lost revenue.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/money-in_icon.svg') }}" alt="cutlery-icon"
                                            width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal md:mb-0">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/money-in_icon.svg') }}"
                                                    alt="cutlery-icon" width="30">
                                            </span>
                                            Rising Operational Costs
                                        </p>
                                        <p class="card_lg-text">
                                            With increased labor and ingredient costs, restaurants are forced to either
                                            raise prices or cut services to stay profitable.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lg rounded-[12px] pr-[10px] md:sticky top-[110px]">
                                <div class="flex gap-[12px] items-start justify-start">
                                    <div class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                        <img src="{{ asset('images/marketing/food-services-premium_icon.svg') }}"
                                            alt="vip-icon" width="30">
                                    </div>
                                    <div class="flex-1">
                                        <p
                                            class="card_lg-title md:text-[18px] font-500 antialiased tracking-normal md:mb-0">
                                            <span
                                                class="btn_icon btn_icon--sm btn bg-gradient-to-tl from-red to-red_light">
                                                <img src="{{ asset('images/marketing/food-services-premium_icon.svg') }}"
                                                    alt="vip-icon" width="30">
                                            </span>
                                            Limited Reservation Availability
                                        </p>
                                        <p class="card_lg-text">
                                            Many genuine diners are turned away due to a lack of available reservations,
                                            missing out on potential revenue opportunities.
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

    <section class="fade-in px-[16px]">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- Consumers -->
                <div class="flex gap-4 md:gap-[40px] flex-col lg:flex-row">
                    <div class="flex-1 text-center">
                        <div class="sticky top-[110px]">
                            <img src="{{ asset('images/marketing/vip-access.png') }}" alt="app-image"
                                class="inline-block rounded-[20px] max-w-[640px] w-full">
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col gap-4 items-start">
                        <span class="tag gap-2 pl-2 font-[600] text-dark font-instrument">
                            <img src="{{ asset('images/marketing/cutlery_icon.svg') }}" alt="cutlery-icon">
                            Welcome to PRIMA
                        </span>
                        <h2 class="text-dark">
                            Introducing PRIMA: Your Solution to Profitable Reservations
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
                                No more bots or fake reservations.
                            </li>
                            <li class="tag pl-2 leading-[1.5] font-[500] font-instrument">
                                <img src="{{ asset('images/marketing/check-green_icon.svg') }}" alt="check-icon">
                                Fewer last-minute cancellations and favors.
                            </li>
                            <li class="tag pl-2 leading-[1.5] font-[500] font-instrument">
                                <img src="{{ asset('images/marketing/check-purple_icon.svg') }}" alt="check-icon">
                                Gain steady high-value customers through concierge networks.
                            </li>
                        </ul>
                        <a href="#"
                            class="btn bg-gradient-to-b from-salmon to-salmon_accent text-white text-center w-full md:w-auto"
                            @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            Talk To PRIMA
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <x-marketing.how-it-work-steps />

    <section class="fade-in px-[16px]">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- Concierges -->
                <div class="flex gap-[16px] lg:gap-10 flex-col lg:flex-row">
                    <div class="flex-1 lg:max-h-[400px]">
                        <img src="{{ asset('images/marketing/restaurant-trusted-network.jpg') }}"
                            alt="access-exclusive-dinning" class="img_full rounded-[20px] lg:object-top">
                    </div>
                    <div class="flex-1 flex flex-col gap-4 items-start justify-center md:py-8">
                        <span class="tag gap-2 pl-2 font-[600] text-dark font-instrument">
                            <img src="{{ asset('images/marketing/group_icon.svg') }}" alt="group_icon">
                            Concierges
                        </span>
                        <h2 class="text-dark">
                            Leverage a Trusted <span class="block">Concierge Network</span>
                        </h2>
                        <p>
                            All reservations are made by trusted concierges from top hotels and luxury communities in
                            major cities. This ensures a consistent flow of high-value clientele, reducing risks and
                            enhancing the dining experience.
                        </p>
                        <a href="#"
                            class="btn bg-gradient-to-b from-salmon to-salmon_accent text-white mt-[10px] md:mt-6 btn_full"
                            @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            Talk To PRIMA
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section style="display: none; opacity: 0" class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-8 items-center">
                <div class="flex flex-col gap-4 items-center text-center">
                    <span class="tag gap-2 pl-2">
                        <img src="{{ asset('images/marketing/money_plus_icon.svg') }}" alt="money-icon">
                        Earnings and Profitability
                    </span>
                    <h2 class="text-dark m-auto">
                        Unlock New Revenue Streams
                    </h2>
                    <p class="max-w-[1100px]">
                        Restaurants earn 60% of the booking fees, while concierges receive up to 15% in commission.
                        PRIMA also gives back 10% of its profits to help feed the homeless, making it a platform with
                        both profit and purpose.
                    </p>
                </div>

                <a href="#" class="btn bg-gradient-to-b from-salmon to-salmon_accent text-white"
                    @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                    Boost Your Business Today!
                </a>

                <div class="flex flex-col gap-6 mt-6 w-full">
                    <div class="flex gap-4 md:gap-10 flex-col md:flex-row">
                        <div class="flex-1 mb-6 md:mb-0">
                            <div class="justify-center items-center flex flex-col relative">
                                <img src="{{ asset('images/marketing/phone-5.png') }}" alt="phone"
                                    class="z-[1] xl:max-w-[520px]">
                                <div
                                    class="box bg-gradient-to-tl from-green_accent to-green_light min-h-[250px] z-[-1] absolute bottom-0 w-full">
                                    <img src="{{ asset('images/marketing/particles.png') }}" alt="particles"
                                        class="img_full absolute top-0 left-0">
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col gap-4">
                            <div class="flex flex-col gap-3">
                                <div class="box border-divider border shadow-2xl flex flex-col gap-4">
                                    <div class="flex gap-4 items-start justify-start">
                                        <div
                                            class="btn_icon btn bg-gradient-to-b from-green_accent to-green_dark_2 md:p-3 rounded-[10px]">
                                            <img src="{{ asset('images/marketing/calendar_icon--light.svg') }}"
                                                alt="calendar-icon" class="w-[30px]">
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bricolage text-dark flex-1">
                                                At 3 reservations per day
                                            </p>
                                            <p class="text-tiny">
                                                Your restaurant can earn an additional <b>$9,900</b> per month.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="box border-divider border shadow-2xl flex flex-col gap-4">
                                    <div class="flex gap-4 items-start justify-start">
                                        <div
                                            class="btn_icon btn bg-gradient-to-b from-green_accent to-green_dark_2 md:p-3 rounded-[10px]">
                                            <img src="{{ asset('images/marketing/schedule_icon--light.svg') }}"
                                                alt="schedule-icon" class="w-[30px]">
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bricolage text-dark flex-1">
                                                Commission Rate: 12% of gross booking fees
                                            </p>
                                            <p class="text-tiny">
                                                Earnings jump to <b>$33,000</b> per month.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="box border-divider border shadow-2xl flex flex-col gap-4">
                                    <div class="flex gap-4 items-start justify-start">
                                        <div
                                            class="btn_icon btn bg-gradient-to-b from-green_accent to-green_dark_2 md:p-3 rounded-[10px]">
                                            <img src="{{ asset('images/marketing/stats-calendar_icon.svg') }}"
                                                alt="stats-calendar-icon" class="w-[30px]">
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bricolage text-dark flex-1">
                                                Commission Rate: 15% of gross booking fees
                                            </p>
                                            <p class="text-tiny">
                                                Your restaurant could see <b>$66,000</b> in extra monthly profit.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="box border-divider border shadow-2xl flex flex-col gap-4">
                                    <div class="flex gap-4 items-start justify-start">
                                        <div
                                            class="btn_icon btn bg-gradient-to-b from-green_accent to-green_dark_2 md:p-3 rounded-[10px]">
                                            <img src="{{ asset('images/marketing/money-in_icon.svg') }}"
                                                alt="money-in-icon" class="w-[30px]">
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-bricolage text-dark flex-1">
                                                At 30 reservations per day
                                            </p>
                                            <p class="text-tiny">
                                                You’ll be looking at nearly <b>$1.2 Million</b> in incremental annual
                                                profit.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in px-[16px]">
        <div class="container pt-0">
            <div class="box_cta bg-gradient-to-t from-blue_deep to-purple_vintage py-[60px]">
                <h3 class="box_cta-title text-center w-full mb-0">
                    Don't Leave Money on the Table!
                </h3>
                <p class="text-center m-auto max-w-[824px]">
                    Take control of your prime-time reservations and ensure a steady stream of revenue. Maximize your
                    profits with PRIMA’s trusted platform.
                </p>
                <a href="#"
                    class="btn bg-white text-primary mt-4 relative z-10 btn_full font-bricolage font-[600] antialiased"
                    @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                    Boost Your Business Today!
                </a>
                <div class="box_cta-deco">
                    <img src="{{ asset('images/marketing/background-left.png') }}" alt="background-left">
                    <img src="{{ asset('images/marketing/background-right.png') }}" alt="background-right">
                </div>
            </div>
        </div>
    </section>

</x-layouts.web>
