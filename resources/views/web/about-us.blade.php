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

    <section class="fade-in md:mt-[20px] lgmt-[40px]">
        <div class="container">
            <div class="flex flex-col gap-4  md:gap-[6">

                <!-- intro about -->
                <div class="flex gap-4 md:gap-[24px] flex-col md:flex-row-reverse mb-[40px]">
                    <div class="flex-1 md:flex md:justify-center md:items-center">
                        <img src="{{ asset('images/marketing/city.jpeg') }}" alt="city" class="img_full rounded-[20px] max-h-[280px] lg:max-h-none">
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-col gap-4 items-start md:px-[11px]">
                            <h2 class="text-dark text-[32px] md:text-[35px] lg:text-[39px] tracking-[-0.03em] md:tracking-normal">
                                Experience Dining Like Never Before with PRIMA
                            </h2>
                            <p class="text-[17px] md:text-[16px]">
                                PRIMA was designed to fix the inefficiencies of the current restaurant reservation systems, which prevent high-value clients from accessing top venues.  By partnering with PRIMA, restaurants participate in a marketplace, where both their PRIME and NON-PRIME reservations are available for booking through a team of vetted and reputable concierges working across the hospitality sector of each city.  With PRIMA, spontaneous customers, those who haven’t made plans weeks or months in advance are given an opportunity to visit the top venues and secure their reservations for a fee, the majority of which goes directly to the restaurant.  With PRIMA, restaurants are able to both boost their bottom line and increase volume of business during slow days and non-prime hours.
                            </p>
                            <a href="#" class="btn  bg-gradient-to-b from-salmon to-salmon_accent text-white mt-6 btn_full"
                               @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            TALK TO PRIMA TO LEARN MORE
                            </a>
                        </div>
                    </div>
                </div>

                <!-- mission vision -->
                <div class="intro_cta intro_cta--2_col gap-[24px]">
                    <div class="box_gradient bg-gradient-to-b from-purple_vintage to-blue_deep md:p-[30px] md:rounded-[30px]">
                        <span class="tag tag--strong pl-2 bg-white text-dark">
                            <img src="{{ asset('images/marketing/shield_icon.svg') }}" alt="shield-icon" class="mx-[6px]">
                            Our Mission
                        </span>
                        <p class="box_gradient-text font-[500] tracking-[-0.03em] md:tracking-normal">
                            To empower diners by providing seamless access to prime time reservations while maximizing profitability for restaurants through a trusted concierge network.
                        </p>
                    </div>
                    <div class="box_gradient bg-gradient-to-b from-blue to-green md:p-[30px] md:rounded-[30px]">
                        <span class="tag tag--strong pl-2 bg-white text-dark">
                            <img src="{{ asset('images/marketing/eye_icon.svg') }}" alt="eye-icon" class="mx-[6px]">
                            Our Vision
                        </span>
                        <p class="box_gradient-text font-[500] tracking-[-0.03em] md:tracking-normal">
                            To revolutionize the dining industry by creating a world where every reservation is a premium experience, ensuring restaurants thrive and diners enjoy exclusive access to sought after venues.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in mt-[60px] md:mt-[100px]">
        <div class="container">
            <div class="flex flex-col gap-4  md:gap-[6">

                <div class="flex gap-4 md:gap-[40px] flex-col md:mb-[40px] mb-[10px] md:flex-row">
                    <div class="flex-1 md:flex md:justify-center md:items-center">
                        <img src="{{ asset('images/marketing/about-us.png') }}" alt="city" class="img_full rounded-[20px] max-h-[280px] lg:max-h-[506px]">
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-col gap-4 items-start">
                            <span class="tag gap-2 pl-2 text-[18px] text-dark font-[600]">
                                <img src="{{ asset('images/marketing/book_icon.png') }}" alt="book-icon" width="24">
                                Our Story
                            </span>
                            <h2 class="text-dark text-[32px] md:text-[35px] lg:text-[39px] tracking-[-0.03em] md:tracking-normal">
                                Our Journey to Transform Dining
                            </h2>
                            <p class="text-[17px] md:text-[16px]">
                                PRIMA was founded by a team of hospitality and online advertising experts who have witnessed the inefficiencies of the current restaurant reservation system. The reality is that most people don’t plan where they want to dine a month in advance. While booking concert tickets well ahead of time makes sense, securing restaurant reservations 30, 60, or more days out is far from ideal and often leads to significant revenue loss and unnecessary work. Many top restaurants experience frequent last-minute cancellations, which could be minimized if diners already present in the area had the opportunity to secure these open reservations. PRIMA facilitates this seamless transaction by offering an innovative platform that unites the hospitality industry, providing real-time access and performance-based tracking of restaurant referrals and commissions for participating concierges. With PRIMA, Everybody Wins. High value clients are able to access impossible-to-book restaurants, restaurants experience incremental revenue and profits, and concierges across the city are better able to serve their customers by providing access to coveted reservations.
                                <br><br>
                                This is only the beginning.
                            </p>
                            <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode->code], false), '/') }}"
                               class="btn bg-gradient-to-b from-blue_dark to-primary_light text-white mt-6 btn_full">
                                Check Availability Now
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

                <div class="flex gap-4 md:gap-[40px] flex-col md:flex-row-reverse mb-[40px]">
                    <div class="flex-1 md:flex md:justify-center md:items-center">
                        <img src="{{ asset('images/marketing/food.jpeg') }}" alt="food" class="img_full rounded-[20px] max-h-[280px] lg:max-h-none">
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-col gap-4 items-start">
                            <span class="tag gap-2 pl-2 text-[18px] text-dark font-[600]">
                                <img src="{{ asset('images/marketing/wallet_icon.png') }}" alt="wallet-icon" width="24">
                                Community Involvement
                            </span>
                            <h2 class="text-dark text-[32px] md:text-[35px] lg:text-[39px] tracking-[-0.03em] md:tracking-normal">
                                Our Commitment to Social Responsibility
                            </h2>
                            <p class="text-[17px] md:text-[16px]">
                                Recognizing that reservations at top venues worldwide are often claimed by affluent consumers, PRIMA is committed to giving back to the communities in each of its participating markets. A percentage of all profits generated through the PRIMA marketplace is donated to support local charities that aid those in need. Additionally, all marketplace participants—including restaurants and concierges—are encouraged to contribute directly through our platform, amplifying the impact of their support.
                                <br><br>
                                If you represent a local charity in a market where PRIMA operates, we welcome you to contact us.
                            </p>
                            <a href="#" class="btn bg-gradient-to-b from-blue to-green text-white mt-[10px] btn_full"
                               @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                            Join Us in Making a Difference
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="fade-in lg:mt-[40px] mt-[20px]">
        <div class="container">
            <div class="flex flex-col gap-8 items-center">
                <div class="flex flex-col gap-[12px] items-start md:items-center md:text-center md:mb-[30px]">
                    <span class="tag gap-2 pl-2 text-[18px] text-dark font-[600]">
                        <img src="{{ asset('images/marketing/dyamond_icon.png') }}" alt="diamond-icon" width="24">
                        Our Values
                    </span>
                    <h2 class="text-dark md:m-auto mb-[0]">
                        Guided by Principles That Matter
                    </h2>
                </div>

                <div class="grid_4x1">
                    <div class="card_square text-center">
                        <div class="card_square-headline flex-col">
                            <div class="btn_icon btn_icon--lg btn bg-gradient-to-tl from-pink to-pink_accent w-[70px] h-[70px]">
                                <img src="{{ asset('images/marketing/vip_icon.svg') }}" alt="vip-icon">
                            </div>
                            <p class="card_square-title mb-[12px] text-[23px]">
                                Integrity
                            </p>
                        </div>
                        <p class="card_square-description mb-[10px] font-[400] text-[17px] md:text-[16px]">
                            We uphold transparency and honesty in all our interactions, ensuring that our clients and partners can trust us to deliver on our promises.
                        </p>
                    </div>

                    <div class="card_square text-center">
                        <div class="card_square-headline flex-col">
                            <div class="btn_icon btn_icon--lg btn bg-gradient-to-l from-primary to-blue_light rounded-[10px] md:p-4 w-[70px] h-[70px]">
                                <img src="{{ asset('images/marketing/schedule_icon--light.svg') }}" alt="schedule-icon">
                            </div>
                            <p class="card_square-title mb-[12px] text-[23px]">
                                Innovation
                            </p>
                        </div>
                        <p class="card_square-description mb-[10px] font-[400] text-[17px] md:text-[16px]">
                            We embrace creativity and
                            continuously seek out cutting-edge solutions that enhance the dining experience for restaurants and consumers alike.
                        </p>
                    </div>

                    <div class="card_square text-center">
                        <div class="card_square-headline flex-col">
                            <div class="btn_icon btn_icon--lg btn bg-gradient-to-r from-purple_light to-purple rounded-[10px] md:p-4 w-[70px] h-[70px]">
                                <img src="{{ asset('images/marketing/group-premium_icon.svg') }}" alt="group-premium-icon">
                            </div>
                            <p class="card_square-title mb-[12px] text-[23px]">
                                Community
                            </p>
                        </div>
                        <p class="card_square-description mb-[10px] font-[400] text-[17px] md:text-[16px]">
                            We believe in supporting the communities we serve, both through charitable efforts and by fostering relationships between restaurants and diners.
                        </p>
                    </div>

                    <div class="card_square text-center">
                        <div class="card_square-headline flex-col">
                            <div class="btn_icon btn_icon--lg btn bg-gradient-to-l from-pink_strong to-blue_accent rounded-[10px] md:p-4 w-[70px] h-[70px]">
                                <img src="{{ asset('images/marketing/food-services_icon.svg') }}" alt="food-services_icon-icon">
                            </div>
                            <p class="card_square-title mb-[12px] text-[23px]">
                                Excellence
                            </p>
                        </div>
                        <p class="card_square-description px-[12px] mb-[10px] font-[400] text-[17px] md:text-[16px]">
                            We strive for excellence in every aspect of our service, from our technology to our customer support, ensuring a seamless experience for all users.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="fade-in md:mt-[70px]">
        <div class="container">
            <div class="box bg-gradient-to-t from-primary to-purple flex flex-col gap-4 px-12px py-[40px] md:py-[60px] lg:py-10 items-center text-white relative text-center ">
                <h2 class="text-[28px] md:text-[32px] md:max-w-[526px] lg:text-[39px] lg:max-w-[626px] tracking-[-0.03em] md:tracking-normal">
                    Become a part of the PRIMA community today.
                </h2>
                <p class="md:max-w-[526px] lg:max-w-[600px] text-[17px]">
                    Experience exclusive dining opportunities and be a part of our mission to enhance the restaurant industry.
                </p>
                <div class="flex gap-[15px] md:gap-[20px] flex-col md:flex-row mt-[15px] mb-[20px] w-full md:w-auto">
                    <a href="#" class="btn border border-white bg-gradient-to-b from-salmon to-salmon_accent text-white btn_full"
                       @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                        Talk to PRIMA
                    </a>
                    <a href="{{ config('app.primary_domain') . '/' . ltrim(route('v.booking', ['code' => $vipCode->code], false), '/') }}"
                       class="btn border border-white bg-gradient-to-b from-sky to-blue_royal text-white btn_full">
                        Book Your Dining Experience
                    </a>
                </div>
                <div class="absolute bottom-0 left-0  justify-between w-full hidden sm:flex pointer-events-none">
                    <img src="{{ asset('images/marketing/background-left.png') }}" alt="background-left">
                    <img src="{{ asset('images/marketing/background-right.png') }}" alt="background-right">
                </div>
            </div>
        </div>
    </section>
</x-layouts.web>
