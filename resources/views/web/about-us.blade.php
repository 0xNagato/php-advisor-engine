@php
    use App\Models\VipCode;
    $vipCode = Cache::remember(
        'available_calendar_button_vip_code_1',
        60,
        fn() => VipCode::query()->where('concierge_id', 1)->active()->first(),
    );
@endphp
<x-layouts.web>

    <x-slot name="title">About Us - PRIMA</x-slot>

    <section class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-4 md:gap-6">

                <!-- intro about -->
                <div class="flex flex-col gap-4 md:gap-10 md:flex-row-reverse">
                    <div class="flex-1">
                        <img src="{{ asset('images/marketing/city.jpeg') }}" alt="city"
                            class="img_full rounded-[20px]">
                    </div>
                    <div class="flex flex-col items-start flex-1 gap-4">
                        <h2 class="text-dark">
                            Experience Dining Like Never Before with PRIMA
                        </h2>
                        <p class="text-tiny">
                            PRIMA was designed to fix the inefficiencies of the current restaurant reservation systems,
                            which prevent high-value clients from accessing top venues. By partnering with PRIMA,
                            restaurants participate in a marketplace, where both their PRIME and NON-PRIME reservations
                            are available for booking through a team of vetted and reputable concierges working across
                            the hospitality sector of each city. With PRIMA, spontaneous customers, those who haven’t
                            made plans weeks or months in advance are given an opportunity to visit the top venues and
                            secure their reservations for a fee, the majority of which goes directly to the restaurant.
                            With PRIMA, restaurants are able to both boost their bottom line and increase volume of
                            business during slow days and non-prime hours.
                        </p>
                        <a href="#" class="mt-6 text-white btn bg-gradient-to-b from-salmon to-salmon_accent"
                            @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            TALK TO PRIMA TO LEARN More
                        </a>
                    </div>
                </div>

                <!-- mision vision -->
                <div class="relative flex flex-col gap-4 mt-6 md:gap-6 lg:flex-row">
                    <div
                        class="flex flex-col items-start flex-1 gap-4 p-6 box bg-gradient-to-b from-purple_vintage to-blue_deep">
                        <span class="gap-2 pl-2 bg-white tag">
                            <img src="{{ asset('images/marketing/shield_icon.svg') }}" alt="shield-icon">
                            Our Mission
                        </span>
                        <p class="antialiased font-semibold tracking-normal text-white font-bricolage text-xl_1">
                            To empower diners by providing seamless access to prime time reservations while maximizing
                            profitability for restaurants through a trusted concierge network.
                        </p>
                    </div>
                    <div class="flex flex-col items-start flex-1 gap-4 p-6 box bg-gradient-to-b from-blue to-green">
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

    <section class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-6">

                <!-- Consumers -->
                <div class="flex flex-col gap-4 md:gap-10 md:flex-row">
                    <div class="flex-1">
                        <img src="{{ asset('images/marketing/about-us.png') }}" alt="access-exclusive-dinning"
                            class="img_full rounded-[20px]">
                    </div>
                    <div class="flex flex-col items-start justify-center flex-1 gap-4">
                        <span class="gap-2 pl-2 tag">
                            <img src="{{ asset('images/marketing/book_icon.png') }}" alt="book-icon" width="24">
                            Our Story
                        </span>
                        <h2 class="text-dark">
                            Our Journey to Transform Dining
                        </h2>
                        <p class="text-tiny">
                            PRIMA was founded by a team of hospitality and online advertising experts who have witnessed
                            the inefficiencies of the current restaurant reservation system. The reality is that most
                            people don’t plan where they want to dine a month in advance. While booking concert tickets
                            well ahead of time makes sense, securing restaurant reservations 30, 60, or more days out is
                            far from ideal and often leads to significant revenue loss and unnecessary work. Many top
                            restaurants experience frequent last-minute cancellations, which could be minimized if
                            diners already present in the area had the opportunity to secure these open reservations.
                            PRIMA facilitates this seamless transaction by offering an innovative platform that unites
                            the hospitality industry, providing real-time access and performance-based tracking of
                            restaurant referrals and commissions for participating concierges. With PRIMA, Everybody
                            Wins. High value clients are able to access impossible-to-book restaurants, restaurants
                            experience incremental revenue and profits, and concierges across the city are better able
                            to serve their customers by providing access to coveted reservations.
                        </p>
                        <p>
                            This is only the beginning.
                        </p>
                        <a href="" class="mt-4 text-white btn bg-gradient-to-b from-blue_dark to-primary_light">
                            Check Availability Now
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container">
            <div class="flex flex-col gap-6">

                <div class="flex flex-col gap-4 md:gap-10 md:flex-row-reverse">
                    <div class="flex-1">
                        <img src="{{ asset('images/marketing/food.jpeg') }}" alt="food"
                            class="img_full rounded-[20px]">
                    </div>
                    <div class="flex flex-col items-start justify-center flex-1 gap-4">
                        <span class="gap-2 pl-2 tag">
                            <img src="{{ asset('images/marketing/wallet_icon.png') }}" alt="wallet-icon" width="24">
                            Community Involvement
                        </span>
                        <h2 class="text-dark">
                            Our Commitment to Social Responsibility
                        </h2>
                        <p class="text-tiny">
                            Recognizing that reservations at top venues worldwide are often claimed by affluent
                            consumers, PRIMA is committed to giving back to the communities in each of its participating
                            markets. A percentage of all profits generated through the PRIMA marketplace is donated to
                            support local charities that aid those in need. Additionally, all marketplace
                            participants—including restaurants and concierges—are encouraged to contribute directly
                            through our platform, amplifying the impact of their support.
                        </p>
                        <p>
                            If you represent a local charity in a market where PRIMA operates, we welcome you to contact
                            us.
                        </p>
                        <a href="#" class="mt-4 text-white btn bg-gradient-to-b from-blue to-green"
                            @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            Join Us in Making a Difference
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container pt-0">
            <div class="flex flex-col items-center gap-8">
                <div class="flex flex-col items-center gap-4 text-center">
                    <span class="gap-2 pl-2 tag">
                        <img src="{{ asset('images/marketing/dyamond_icon.png') }}" alt="dyamond-icon" width="24px">
                        Our Values
                    </span>
                    <h2 class="m-auto text-dark">
                        Guided by Principles That Matter
                    </h2>
                </div>

                <div class="grid w-full grid-cols-1 gap-4 mt-6 2 md:grid-cols-2 lg:grid-cols-4">
                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div class="btn_icon btn bg-gradient-to-tl from-pink to-pink_accent rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip-icon"
                                    class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                integrity
                            </p>
                        </div>
                        <p class="text-tiny">
                            We uphold transparency and honesty in all our interactions, ensuring that our clients and
                            partners can trust us to deliver on our promises.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 p-6 text-left border shadow-2xl box border-divider md:text-center">
                        <div class="flex items-center gap-4 md:flex-col">
                            <div class="btn_icon btn bg-gradient-to-l from-primary to-blue_light rounded-[10px] md:p-4">
                                <img src="{{ asset('images/marketing/schedule_icon--light.svg') }}" alt="schedule-icon"
                                    class="w-[30px] md:w-[40px]">
                            </div>
                            <p class="flex-1 text-xl font-bricolage text-dark">
                                Innovation
                            </p>
                        </div>
                        <p class="text-tiny">
                            We embrace creativity and continuously seek out cutting-edge solutions that enhance the
                            dining experience for restaurants and consumers alike.
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
                                Community
                            </p>
                        </div>
                        <p class="text-tiny">
                            We believe in supporting the communities we serve, both through charitable efforts and by
                            fostering relationships between restaurants and diners.
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
                                Excellence
                            </p>
                        </div>
                        <p class="text-tiny">
                            We strive for excellence in every aspect of our service, from our technology to our customer
                            support, ensuring a seamless experience for all users.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in">
        <div class="container pt-0">
            <div
                class="relative flex flex-col items-center gap-4 py-10 text-center text-white box bg-gradient-to-t from-primary to-purple">
                <h2 class="max-w-[500px]">
                    Become a part of the PRIMA community today.
                </h2>
                <p class="max-w-[600px] text-tiny">
                    Experience exclusive dining opportunities and be a part of our mission to enhance the restaurant
                    industry.
                </p>
                <div class="flex flex-col gap-2 mt-6 sm:gap-4 sm:flex-row">
                    <a href="#"
                        class="relative z-10 text-white border border-white btn bg-gradient-to-b from-salmon to-salmon_accent"
                        @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                        Talk to PRIMA
                    </a>
                    <a href="{{ route('v.booking', ['code' => $vipCode->code]) }}"
                        class="relative z-10 text-white border border-white btn bg-gradient-to-b from-sky to-blue_royal">
                        Book Your Dining Experience
                    </a>
                </div>
                <div class="absolute bottom-0 left-0]  justify-between w-full hidden sm:flex">
                    <img src="{{ asset('images/marketing/background-left.png') }}" alt="background-left">
                    <img src="{{ asset('images/marketing/background-right.png') }}" alt="background-right">
                </div>
            </div>
        </div>
    </section>
</x-layouts.web>
