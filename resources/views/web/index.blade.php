<!DOCTYPE html>
<html>

<head>
    <title>PRIMA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@100..900&display=swap"
        rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

    <script src="https://www.youtube.com/iframe_api"></script>
    @filamentStyles
    @vite('resources/css/web.css')

    <style>
        :root {
            --plyr-color-main: #5046E5;
            --plyr-video-background: #FFF;
        }
    </style>
</head>

<body x-data="modalHandler">
    {{-- Modals --}}
    <div class="h-0">
        <x-filament::modal id="contact" width="2xl" :close-button="true">
            <x-slot name="heading">
                Talk to PRIMA
            </x-slot>
            <livewire:talk-to-prima />
        </x-filament::modal>

        <x-filament::modal id="video" width="7xl" :close-button="true">
            <x-slot name="heading">
                Watch Our Explainer
            </x-slot>
            <div class="youtube-player-modal" data-plyr-provider="youtube" data-plyr-embed-id="pxyHz-RjHW0"></div>
        </x-filament::modal>
    </div>

    <!--- header --->
    <header class="sticky top-0 z-50">
        <!--- announcement bar --->
        <div class="bg-indigo-600">
            <div
                class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[1035px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
                <p
                    class="text-[15px] px-[0] py-[10px] not-italic font-semibold text-center text-white leading-[normal] md:py-[7px]">
                    PRIMA is LIVE in Miami and Los Angeles!
                </p>
            </div>
        </div>
        <!--- announcement bar end --->
        <div class="border-b bg-[#F9F9F9] relative">
            <div class="pl-[40px] pr-[65px] md:max-w-[1035px] mx-[auto] md:my-[0] md:pl-[50px] md:pr-[50px] w-full">
                <div class="flex items-center justify-between">
                    <div class="max-w-[72px] md:max-w-[75px]">
                        <a href="#" class="block pl-[0] pr-[0] py-[21px] md:pl-[0] md:pr-[0] md:py-[13px]">
                            <img src="/assets/images/logo.png" width="auto" height="auto" loading="lazy"
                                alt="logo" class="block">
                        </a>
                    </div>
                    <div class="space-x-1 header_button">
                        @auth()
                            {{-- Welcome message to first name --}}
                            <span class="hidden text-sm font-semibold md:inline-block">Welcome back,
                                {{ auth()->user()->first_name }}</span>

                            <a href="/platform/"
                                class="leading-[normal] hidden md:inline-block text-[#5249C4] rounded-[4px] border-[1px] border-[solid] border-[#5249C4]  px-[10px] py-[7px] text-[10px] md:px-[24px] md:py-[5px] md:text-[14px] [transition:all_.5s_ease] hover:bg-[#5046E5] hover:text-[#fff]  font-semibold">
                                Dashboard
                            </a>

                            <form action="/platform/logout" method="POST" class="inline-block">
                                @csrf
                                <button type="submit"
                                    class="leading-[normal] hidden md:inline-block text-[#5249C4] rounded-[4px] border-[1px] border-[solid] border-[#5249C4] px-[10px] py-[7px] text-[10px] md:px-[24px] md:py-[5px] md:text-[14px] [transition:all_.5s_ease] hover:bg-[#5046E5] hover:text-[#fff] font-semibold">
                                    Sign Out
                                </button>
                            </form>
                        @else
                            <a class="leading-[normal] hidden md:inline-block text-[#5249C4] rounded-[4px] border-[1px] border-[solid] border-[#5249C4]  px-[10px] py-[7px] text-[10px] md:px-[24px] md:py-[5px] md:text-[14px] [transition:all_.5s_ease] hover:bg-[#5046E5] hover:text-[#fff]  font-semibold"
                                href="/platform/login">
                                Login
                            </a>
                        @endauth
                    </div>
                    <div class="menu_trigger_js absolute right-[20px] top-[17px] md:hidden">
                        <img src="/assets/images/icon-close.png" width="20" height="auto" loading="lazy"
                            alt="icon-close" class="block" style="display:none;">
                        <img src="/assets/images/icon-menu.png" width="25" height="auto" loading="lazy"
                            alt="icon-menu" class="block">
                    </div>
                    <div class="p-6 border-b bg-[#F9F9F9] absolute top-[100%] left-[0] right-[0] md:hidden"
                        style="display:none">
                        @auth()
                            <a href="/platform/"
                                class="block text-[#5249C4] font-semibold text-[14px] leading-[normal] py-[10px]">Dashboard</a>
                            <form action="/platform/logout" method="POST" class="inline-block">
                                @csrf
                                <button type="submit"
                                    class="block text-[#5249C4] font-semibold text-[14px] leading-[normal] py-[10px]">
                                    Sign Out
                                </button>
                            </form>
                        @else
                            <a class="block text-[#5249C4] font-semibold text-[14px] leading-[normal] py-[10px]"
                                href="/platform/login">Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!--- header end --->
    <!--- Section1 --->
    <section class="pt-[11px] px-[0] pb-[20px] text-center md:pt-[37px] md:pb-[30px] md:text-left">
        <div
            class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1035px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="flex flex-wrap items-center">
                <div class="w-12/12 md:w-6/12">
                    <img src="/assets/images/image1.png" width="auto" height="auto" loading="lazy" alt="logo"
                        class="block">
                </div>
                <div class="w-12/12 md:w-6/12 md:pl-[56px]">
                    <h1
                        class="not-italic font-normal text-[26.25px] mt-[20px] md:mt-[0px] md:text-[34.117px] leading-[100%] ">
                        PRIMA Increases Venue Profitability While Reducing Bots, Fake Reservations And
                        Cancellations.</h1>
                    <p class="not-italic font-normal md:pt-8 pt-[15px] text-[14px] md:text-[18px]">PRIMA's Concierge
                        Network
                        helps to fill dining rooms with the best customers at all times.</p>
                    <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] md:min-h-[57.953px] text-[14px] md:text-[18.196px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5] mt-[26px] md:mt-[32px]"
                        href="#" @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                        Talk to PRIMA →
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!--- Section1 End --->
    <section class="pt-[20px] px-[0] pb-[18px] md:pt-[30px] md:pb-[48px]">
        <div class="max-w-4xl mx-[auto] w-full youtube-container">

            <div class="youtube-player-main" data-plyr-provider="youtube" data-plyr-embed-id="pxyHz-RjHW0"></div>

        </div>
    </section>
    <!--- Section2 --->
    <section class="pt-[20px] px-[0] pb-[18px] md:pt-[30px] md:pb-[48px]">
        <div
            class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full  md:my-[0] md:pl-[50px] md:pr-[50px] md:max-w-[1043px]">
            <h2 class="not-italic font-normal text-center text-[36px] leading-[normal]">Available On PRIMA</h2>
            <!--- Partner Images --->
            <div class="pt-[11px]">
                <!-- Desktop Layout -->
                <div
                    class="hidden md:grid grid-cols-6 gap-8 items-center justify-items-center max-w-[1000px] mx-auto mt-4">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/mandolin.png" width="auto"
                        height="auto" loading="lazy" alt="Mandolin" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/margot-sobe.png" width="auto"
                        height="auto" loading="lazy" alt="Margot" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/paya.png" width="auto"
                        height="auto" loading="lazy" alt="Paya" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/sereia.png" width="auto"
                        height="auto" loading="lazy" alt="Sereia" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/sparrow.png" width="auto"
                        height="auto" loading="lazy" alt="Sparrow" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/latelier-de-joel-robuchon.png"
                        width="auto" height="auto" loading="lazy" alt="Le Jardinier" class="max-h-20">
                </div>

                <!-- Mobile Layout -->
                <div class="grid grid-cols-2 gap-6 md:hidden mt-4 max-w-[300px] mx-auto">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/mandolin.png" width="auto"
                        height="auto" loading="lazy" alt="Mandolin" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/margot-sobe.png" width="auto"
                        height="auto" loading="lazy" alt="Margot" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/paya.png" width="auto"
                        height="auto" loading="lazy" alt="Paya" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/sereia.png" width="auto"
                        height="auto" loading="lazy" alt="Sereia" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/sparrow.png" width="auto"
                        height="auto" loading="lazy" alt="Sparrow" class="max-h-20">
                    <img src="https://prima-bucket.nyc3.digitaloceanspaces.com/venues/latelier-de-joel-robuchon.png"
                        width="auto" height="auto" loading="lazy" alt="Le Jardinier" class="max-h-20">
                </div>
            </div>
            <!--- Partner Images End --->
        </div>
    </section>
    <!--- Section2 End --->
    <div class="max-w-[1048.223px] h-[0.5px] bg-[#000] hidden md:block mx-[auto] w-full  md:my-[0]"></div>
    <!--- Section3 --->
    <section class="pt-[15px] px-[0] pb-[50px] md:pt-[50px] md:pb-[25px]">
        <div class="max-w-full pl-[30px] pr-[30px] md:max-w-[809px] md:pl-[50px] md:pr-[50px] w-full mx-[auto] my-[0]">
            <div class="flex flex-wrap items-baseline">
                <div class="md:max-w-[50%] md:pr-[63px] md:border-r-[.5px] md:[border-right-color:#000]">
                    <div
                        class="text-[14px] font-medium leading-[normal] text-center rounded-[15.157px] bg-[#818181] px-[10px] py-[5px] text-[#fff]">
                        THE PROBLEM
                    </div>
                    <h2 class="text-[23.25px] leading-[100%] mt-[30px]">Many Top Venues Are Fully Booked,
                        Sometimes
                        Months In Advance!</h2>
                    <p class="text-[15px] leading-[normal] mt-[20px]">Affluent consumers cannot gain access to these
                        venues, and bots have overtaken reservation systems creating fake bookings only to re-sell
                        them
                        on the grey market for a profit.
                    </p>
                    <h3 class="text-[20.25px] not-italic mt-[12px]">This is NOT the way it should be.</h3>
                </div>
                <div class="md:pl-[75px] md:max-w-[50%] pt-[47px] md:pt-[0px]">
                    <div
                        class="text-[14px] font-medium leading-[normal] text-center rounded-[15.157px] bg-[#5046E5] px-[10px] py-[5px] text-[#fff]">
                        INTRODUCING PRIMA VIP
                    </div>
                    <h2 class="text-[23.25px] leading-[100%] mt-[30px] capitalize">
                        PRIMA allows in-demand venues to take control over the sale of their prime time reservations.
                    </h2>
                    <p class="text-[15px] leading-[normal] mt-[20px]">
                        Reservations are sold through a network of vetted and reputable hospitality concierges,
                        eliminating
                        bots, fake reservations and last minute cancellations.
                        All while helping fill dining rooms during non-peak hours.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!--- Section3 End --->
    {{-- <section class="max-w-4xl mx-auto">
    <img src="/assets/images/venue2.jpeg" width="auto" height="auto" loading="lazy"
         alt="Venue" class="block">
</section> --}}
    <!--- Section4 --->
    <section class="pt-[50px] md:pt-[30px] px-[0] pb-[20px] md:pb-[50px]">
        <div
            class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[1035px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="pb-[21px] md:pb-[53px] text-center">
                <div
                    class="px-[34px] py-[5px] rounded-[15.157px] bg-[#5046E5] font-medium text-center inline-flex  text-white text-[14px]">
                    Why CHOOSE PRIMA?
                </div>
                <h2 class="text-[30.25px] leading-[115.8%] max-w-[460px] mx-[auto] my-[0] pt-[24px]">Revolutionize Your
                    Reservation System with PRIMA</h2>
            </div>
            <div class="hidden md:block">
                <div>
                    <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                        <!--- Section4 Item --->
                        <div
                            class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon1.png" width="33" height="auto" alt="icon1"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Maximize Revenue from Prime-Time
                                Reservations</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Directly sell
                                your
                                prime-time tables to high-value clients through PRIMA, ensuring maximum revenue from
                                each
                                booking and eliminating losses to the grey market.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon2.png" width="33" height="auto" alt="icon2"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Real-Time Reservation Management</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Manage your
                                reservations with real-time updates, reducing the risk of over-bookings and ensuring
                                optimal
                                table occupancy at all times.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon3.png" width="33" height="auto" alt="icon3"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Automated Concierge Commissions</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Automate
                                commission
                                calculations and payments, making it easy for concierges to earn and for venues to
                                manage payouts efficiently.</p>
                        </div>
                        <!--- Section4 Item End --->
                    </div>
                </div>
                <div class="md:pt-[41px]">
                    <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                        <!--- Section4 Item --->
                        <div
                            class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon4.png" width="33" height="auto" alt="icon4"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Attract High-Value Clients</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Offer exclusive
                                reservations to high-value clients who are willing to pay a premium, elevating your
                                venue’s status and attracting discerning diners.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon5.png" width="33" height="auto" alt="icon5"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Comprehensive Analytics and Insights
                            </h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Access detailed
                                analytics on reservations, revenue, and customer satisfaction, helping you make informed
                                decisions to enhance your service and profitability.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon6.png" width="33" height="auto" alt="icon6"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Support Ethical Practices and
                                Community
                                Impact</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Participate in
                                ethical
                                practices by ensuring fair reservations and supporting local businesses. A portion of
                                PRIMA's fees is donated to social causes, including feeding the homeless.</p>
                        </div>
                        <!--- Section4 Item End --->
                    </div>
                </div>
            </div>

            <div class="section4_slider_js md:hidden">
                <div class="px-[5px] py-[0]">
                    <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                        <!--- Section4 Item --->
                        <div
                            class="min-h-[280px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon1.png" width="33" height="auto" alt="icon1"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Maximize Revenue from Prime-Time
                                Reservations</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Directly sell
                                your
                                prime-time tables to high-value clients through PRIMA, ensuring maximum revenue from
                                each
                                booking and eliminating losses to the grey market.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="min-h-[288px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon2.png" width="33" height="auto" alt="icon2"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Real-Time Reservation Management</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Manage your
                                reservations with real-time updates, reducing the risk of over-bookings and ensuring
                                optimal
                                table occupancy at all times.</p>
                        </div>
                        <!--- Section4 Item End --->
                    </div>
                </div>

                <div class="px-[5px] py-[0]">
                    <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                        <!--- Section4 Item --->
                        <div
                            class="min-h-[280px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon3.png" width="33" height="auto" alt="icon3"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Automated Concierge Commissions</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Automate
                                commission
                                calculations and payments, making it easy for concierges to earn and for venues to
                                manage payouts efficiently.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="min-h-[288px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon4.png" width="33" height="auto" alt="icon4"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Attract High-Value Clients</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Offer exclusive
                                reservations to high-value clients who are willing to pay a premium, elevating your
                                venue’s status and attracting discerning diners.</p>
                        </div>
                        <!--- Section4 Item End --->
                    </div>
                </div>
                <div class="px-[5px] py-[0]">
                    <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                        <!--- Section4 Item --->
                        <div
                            class="min-h-[280px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon5.png" width="33" height="auto" alt="icon5"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Comprehensive Analytics and Insights
                            </h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Access detailed
                                analytics on reservations, revenue, and customer satisfaction, helping you make informed
                                decisions to enhance your service and profitability.</p>
                        </div>
                        <!--- Section4 Item End --->
                        <!--- Section4 Item --->
                        <div
                            class="min-h-[288px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                            <img src="/assets/images/icon6.png" width="33" height="auto" alt="icon6"
                                loading="lazy">
                            <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Support Ethical Practices and
                                Community
                                Impact</h3>
                            <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Participate in
                                ethical
                                practices by ensuring fair reservations and supporting local businesses. A portion of
                                PRIMA's fees is donated to social causes, including feeding the homeless.</p>
                        </div>
                        <!--- Section4 Item End --->
                    </div>
                </div>
            </div>

        </div>
    </section>
    <!--- Section4 End --->
    <!--- Section5 --->
    <section class="pt-[27px] md:pt-[43px] bg-[linear-gradient(transparent_77%,_#5046E5_113px)]">
        <div
            class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[820px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="flex flex-col-reverse flex-wrap md:flex-row">
                <div class="section5_image md:max-w-[50%] w-full">
                    <img src="/assets/images/image2.png" width="auto" height="auto" loading="lazy"
                        alt="logo" class="block max-w-[239px] mx-[auto] my-[0] md:max-w-full">
                </div>
                <div class="section5_text_main md:max-w-[50%] md:pl-[50px] w-full pb-[50px] md:pb-[0px]">
                    <h2 class="text-[26.34px] leading-[115.8%] hidden md:block capitalize">
                        No more bots and no more last minute cancellations.
                    </h2>
                    <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5] md:mt-[26px] md:mt-[34px]"
                        href="#" @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">Talk to
                        PRIMA →</a>
                    <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[8px] underline [text-underline-offset:3px]"
                        href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">
                        Watch Our Explainer
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!--- Section5 End --->
    <!--- Section6 --->
    <section class="pt-[51px] px-[0] pb-[100px] md:pt-[65px] md:pb-[81px]">
        <div
            class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[1244px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="pb-[45px] md:pb-[65px] text-center">
                <h2 class="text-[30.25px] leading-[115.8%]">How PRIMA Works</h2>
                <p class="text-[17px] leading-[normal] max-w-[248px] mx-[auto] my-[0] pt-[12px]">A Seamless Solution
                    for
                    Venues and Concierges</p>
            </div>
            <div class="flex flex-wrap items-stretch">
                <!--- Section6 Item --->
                <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                    <div
                        class="border-t-[unset] border-l w-px h-full left-[18px] top-[20px] w-full block md:border-l-0 absolute md:left-2/4 md:top-[18px] md:border-t border-dashed border-[#000] md:border-t-[#000] md:w-full">
                    </div>
                    <div
                        class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                        1
                    </div>
                    <h3
                        class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">
                        Join
                        PRIMA</h3>
                    <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">
                        Venues
                        join PRIMA and define their booking hours and PRIME and NON-PRIME dining times.</p>
                </div>
                <!--- Section6 Item End --->
                <!--- Section6 Item --->
                <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                    <div
                        class="border-t-[unset] border-l w-px h-full left-[18px] top-[20px] w-full block md:border-l-0 absolute md:left-2/4 md:top-[18px] md:border-t border-dashed border-[#000] md:border-t-[#000] md:w-full">
                    </div>
                    <div
                        class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                        2
                    </div>
                    <h3
                        class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">
                        Choose Venue</h3>
                    <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">
                        Concierges across the PRIMA Network have access to the PRIMA Availability Calendar</p>
                </div>
                <!--- Section6 Item End --->
                <!--- Section6 Item --->
                <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                    <div
                        class="border-t-[unset] border-l w-px h-full left-[18px] top-[20px] w-full block md:border-l-0 absolute md:left-2/4 md:top-[18px] md:border-t border-dashed border-[#000] md:border-t-[#000] md:w-full">
                    </div>
                    <div
                        class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                        3
                    </div>
                    <h3
                        class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">
                        Book
                        through Concierge Network</h3>
                    <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">
                        Prime-Time Reservations are available for sale. Non-Prime Reservations are booked without fees.
                    </p>
                </div>
                <!--- Section6 Item End --->
                <!--- Section6 Item --->
                <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                    <div
                        class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                        4
                    </div>
                    <h3
                        class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">
                        Real-Time Reporting</h3>
                    <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">All
                        revenues and reservations are tracked in real time.</p>
                </div>
                <!--- Section6 Item End --->
            </div>
            <div class="max-w-[630px] mx-[auto] my-[0] pt-[38px] md:pt-[82px] text-center">
                <div class="flex flex-wrap md:flex-nowrap md:gap-x-[17px] md:flex-row-reverse">
                    <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5]"
                        href="#" @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">Talk to
                        PRIMA →</a>
                    <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[6px] md:mt-[0px] underline [text-underline-offset:3px]"
                        href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">Watch Our
                        Explainer</a>
                </div>
            </div>
        </div>
    </section>
    <!--- Section6 End --->
    <div class="max-w-[262px] md:max-w-[1048.223px] h-[0.5px] bg-[#000]  mx-[auto] w-full  md:my-[0]"></div>
    <!--- Section7 --->
    <section class="pt-[33px] px-[0] pb-[30px] md:pt-[61px] md:pb-[105px]">
        <div
            class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1148px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="flex flex-wrap items-stretch">
                <!--- Section7 Left --->
                <div class="md:border-r border-[#000] md:max-w-[50%] w-full text-center relative">
                    <img src="/assets/images/image3.png" width="auto" height="auto" loading="lazy"
                        alt="logo" class="block hidden md:block max-w-[265px] mx-[auto] my-[0]">
                    <h3 class="text-[30.25px] leading-[115.8%] max-w-[305px] mx-[auto] my-[0] md:pt-[25px]">With PRIMA,
                        Everybody Wins™</h3>
                    <p class="text-[16px] leading-[normal] max-w-[308px] mx-[auto] my-[0] pt-[29px]">
                        <span class="flex items-baseline gap-[7px] text-left">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy"> Venues maximize their revenue. </span>
                        <span class="flex items-baseline gap-[7px] text-left mt-[20px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy"> Concierges earn additional income. </span>
                        <span class="flex items-baseline gap-[7px] text-left mt-[20px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy"> Diners enjoy their favorite venues at their chosen time. </span>
                    </p>
                </div>
                <!--- Section7 Left End --->
                <!--- Section7 Right --->
                <div class="md:max-w-[50%] md:pl-[54px] pt-[51px] md:pt-[0px] w-full text-center relative">
                    <h3 class=" text-[30.25px] leading-[115.8%] max-w-[250px] mx-[auto] my-[0] md:max-w-full">
                        PRIMA vs. Grey Market
                    </h3>
                    <div class="pt-[36px] md:pt-[49px] flex">
                        <div class="w-6/12">
                            <h4 class="text-[21px] leading-[normal]">OLD WAY</h4>
                            <p class="pt-[29px] text-[15.67px] font-light leading-[normal]">Grey Market Fee</p>
                            <p class="text-[15.67px] font-medium leading-[normal] pt-[10px]">$200</p>
                            <div class="gap-[6px] mt-[17px] flex flex-col">
                                <div
                                    class="h-[284px] bg-[#ABABAB] gap-[26px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                    <div class="text-[12.71px] font-medium leading-[normal]">Grey Market Middlemen
                                    </div>
                                    <div class="text-[12.71px] font-medium leading-[normal]">80%</div>
                                </div>
                                <div
                                    class="h-[65px] bg-[#818181] gap-[10px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                    <div class="text-[12.71px] font-medium leading-[normal]">Tips And Bribes</div>
                                    <div class="text-[12.71px] font-medium leading-[normal]">20%</div>
                                </div>
                            </div>
                        </div>
                        <div class="w-6/12">
                            <h4 class="text-[21px] leading-[normal] font-bold font-[Inter] text-[#5046E5]">PRIMA</h4>
                            <p class="pt-[29px] text-[15.67px] font-light leading-[normal]">Reservation Fee</p>
                            <p class="text-[15.67px] font-medium leading-[normal] pt-[10px]">$200</p>
                            <div class="gap-[6px] mt-[17px] flex flex-col">
                                <div
                                    class="h-[182px] bg-indigo-300 gap-[27px] pb-[50px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                    <div class="text-[12.71px] font-medium leading-[normal]">Venue Commission
                                    </div>
                                    <div class="text-[12.71px] font-medium leading-[normal]">60%</div>
                                </div>
                                <div
                                    class="bg-indigo-400 h-[65px] gap-[4px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                    <div class="text-[12.71px] font-medium leading-[normal]">Concierge Service</div>
                                    <div class="text-[12.71px] font-medium leading-[normal]">10-15%</div>
                                </div>
                                <div
                                    class="h-[75px] text-white bg-indigo-600 gap-[2px] pb-[10px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                    <div class="text-[12.71px] font-medium leading-[normal]">PRIMA</div>
                                </div>
                                <div
                                    class="bg-indigo-700 text-white h-[25px] flex-row gap-[7px] -mt-[6px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex items-center justify-center">
                                    <div class="text-[12.71px] font-medium leading-[normal]">CHARITY</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--- Section7 Right End --->
            </div>
            <div class="max-w-[630px] mx-[auto] my-[0] pt-[34px] md:pt-[82px] text-center">
                <h2 class="text-[28.177px] leading-[115.8%] pb-[20px] md:pb-[37px]">
                    PRIMA Provides Additional Bottom-Line Profits To Each Participating Venue, Without Additional Fees.
                </h2>
                <div class="flex flex-wrap md:flex-nowrap md:gap-x-[17px] md:flex-row-reverse">
                    <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5]"
                        href="#" @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">Talk to
                        PRIMA →</a>
                    <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[6px] md:mt-[0px] underline [text-underline-offset:3px]"
                        href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">Watch Our
                        Explainer</a>
                </div>
            </div>
        </div>
    </section>
    <!--- Section7 End --->
    <div class="max-w-[262px] md:max-w-[1048.223px] h-[0.5px] bg-[#000]  mx-[auto] w-full  md:my-[0]"></div>
    <!--- Section8 --->
    <section class="pt-[25px] px-[0] pb-[40px] md:pt-[61px] md:pb-[99px]">
        <div
            class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1170px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="pb-[55px] text-center">
                <h2 class="text-[28.177px] leading-[115.8%] pt-[16px]">Transforming the Hospitality Industry</h2>
                <p
                    class="text-[16px] not-italic font-normal leading-[normal] pt-[25px] max-w-[590px] mx-[auto] my-[0]">
                    PRIMA not only enhances the reservation process - our platform ensures fair practices, supports
                    local
                    businesses, and contributes to social causes. <strong class="block pt-[20px]">Here's how we make a
                        difference for:</strong>
                </p>
            </div>
            <div class="md:hidden flex gap-[6px]">
                <h3 class="text-[rgba(0,_0,_0,_0.50)] text-[16.07px] px-[0] py-[11px] max-w-[calc(33.33% -(6px)* 2 / 3)] bg-[#F4F4F4] border-[.34px] border-[solid] border-[rgba(0,0,0,0.50)] [transition:all_.5s_ease] w-full text-center section8_js_trigger active"
                    data_tab="1">Diners</h3>
                <h3 class="text-[rgba(0,_0,_0,_0.50)] text-[16.07px] px-[0] py-[11px] max-w-[calc(33.33% -(6px)* 2 / 3)] bg-[#F4F4F4] border-[.34px] border-[solid] border-[rgba(0,0,0,0.50)] [transition:all_.5s_ease] w-full text-center section8_js_trigger"
                    data_tab="2">Venues</h3>
                <h3 class="text-[rgba(0,_0,_0,_0.50)] text-[16.07px] px-[0] py-[11px] max-w-[calc(33.33% -(6px)* 2 / 3)] bg-[#F4F4F4] border-[.34px] border-[solid] border-[rgba(0,0,0,0.50)] [transition:all_.5s_ease] w-full text-center section8_js_trigger"
                    data_tab="3">Concierges</h3>
            </div>
            <div class="flex items-stretch flex-wrap section8_slider gap-[53px] flex-nowrap">
                <!--- Section8 Item --->
                <div class="section8_js_trigger_item border-[0.3px] border-[solid] border-[rgba(0,0,0,0.30)] bg-[rgba(255,_255,_255,_0.30)] [box-shadow:2px_4px_4px_0px_rgba(0,_0,_0,_0.10)] mt-[7px] md:mt-[48px] px-[14px] py-[0] w-full md:w-4/12 pb-[29px]  text-center relative active"
                    data_tab="1">
                    <h3
                        class="text-[26.07px] font-semibold leading-[normal] font-[Inter] absolute -top-[47px] left-[0] right-[0] p-[4px] bg-[#5046E5] hidden md:block text-white">
                        Diners</h3>
                    <h3 class="pt-[29px] text-[25.18px] font-normal leading-[115.8%]">Exclusive Dining Experiences Made
                        Easy</h3>
                    <p class="pt-[20px] text-[16px] font-light leading-[normal]">With PRIMA, you can secure prime-time
                        tables at the best venues effortlessly. </p>
                    <p class="pt-[18px] max-w-full text-[16px] leading-[normal] mx-[auto] my-[0]">
                        <span class="block pl-[21px] relative text-left">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Guaranteed
                                Reservations</strong> Secure tables at fully booked, high-demand venues </span>
                        <span class="block pl-[21px] relative text-left mt-[21px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Convenience</strong>
                            Effortless booking process through our user-friendly platform. </span>
                        <span class="block pl-[21px] relative text-left mt-[21px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Exclusive
                                Access</strong> Access prime-time reservations and unique dining experiences. </span>
                    </p>
                </div>
                <!--- Section8 Item End --->
                <!--- Section8 Item --->
                <div class="section8_js_trigger_item border-[0.3px] border-[solid] border-[rgba(0,0,0,0.30)] bg-[rgba(255,_255,_255,_0.30)] [box-shadow:2px_4px_4px_0px_rgba(0,_0,_0,_0.10)] mt-[7px] md:mt-[48px] px-[14px] py-[0] w-full md:w-4/12 text-center relative"
                    data_tab="2">
                    <h3
                        class="text-[26.07px] font-semibold leading-[normal] font-[Inter] absolute -top-[47px] left-[0] right-[0] p-[4px] bg-[#5046E5] hidden md:block text-white">
                        Venues</h3>
                    <h3 class="pt-[29px] text-[25.18px] font-normal leading-[115.8%]">Maximize Profits and Control Your
                        Reservations</h3>
                    <p class="pt-[20px] text-[16px] font-light leading-[normal]">Eliminate the grey market, regain
                        control
                        over your reservation book, and ensure that your tables are always filled.</p>
                    <p class="pt-[18px] max-w-full text-[16px] leading-[normal] mx-[auto] my-[0]">
                        <span class="block pl-[21px] relative text-left">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Increased
                                Revenue</strong> Sell prime-time reservations directly to diners willing to pay a
                            premium </span>
                        <span class="block pl-[21px] relative text-left mt-[21px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Control</strong>
                            Regain control over your reservation book and eliminate third-party middlemen. </span>
                        <span class="block pl-[21px] relative text-left mt-[21px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Fill
                                Non-Prime Slots</strong> Incentivize concierges to book non-prime time slots, ensuring
                            full occupancy. </span>
                    </p>
                    <p>
                        <a href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })"
                            class="inline-block mt-[32px] underline text-[16px] font-medium leading-[120%] mb-[30px] [text-underline-offset:2px]">
                            LEARN MORE
                        </a>
                    </p>
                </div>
                <!--- Section8 Item End --->
                <!--- Section8 Item --->
                <div class="section8_js_trigger_item border-[0.3px] border-[solid] border-[rgba(0,0,0,0.30)] bg-[rgba(255,_255,_255,_0.30)] [box-shadow:2px_4px_4px_0px_rgba(0,_0,_0,_0.10)] mt-[7px] md:mt-[48px] px-[14px] py-[0] w-full md:w-4/12 text-center relative"
                    data_tab="3">
                    <h3
                        class="text-[26.07px] font-semibold leading-[normal] font-[Inter] absolute -top-[47px] left-[0] right-[0] p-[4px] bg-[#5046E5] hidden md:block text-white">
                        Concierges</h3>
                    <h3 class="pt-[29px] text-[25.18px] font-normal leading-[115.8%]">Enhance Your Service and Earn
                        Commissions</h3>
                    <p class="pt-[20px] text-[16px] font-light leading-[normal]">Earn attractive commissions by
                        securing
                        reservations for high-demand venues.</p>
                    <p class="pt-[18px] max-w-full text-[16px] leading-[normal] mx-[auto] my-[0]">
                        <span class="block pl-[21px] relative text-left">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Earnings</strong>
                            Earn commissions on every reservation booked through the platform. </span>
                        <span class="block pl-[21px] relative text-left mt-[21px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Professional
                                Network</strong> Be part of a prestigious network of concierges and hospitality
                            professionals. </span>
                        <span class="block pl-[21px] relative text-left mt-[21px]">
                            <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                loading="lazy" class="absolute left-[0] top-[3px]">
                            <strong
                                class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Client
                                Satisfaction</strong> Provide your clients with exclusive access to top dining
                            experiences. </span>
                    </p>
                    <p>
                        <a href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })"
                            class="inline-block mt-[32px] underline text-[16px] font-medium leading-[120%] mb-[30px] [text-underline-offset:2px]">
                            LEARN MORE
                        </a>
                    </p>
                </div>
                <!--- Section8 Item End --->
            </div>
        </div>
    </section>
    <!--- Section8 End --->
    <div class="max-w-[1048.223px] h-[0.5px] bg-[#000] hidden md:block mx-[auto] w-full  md:my-[0]"></div>
    <img src="/assets/images/industry_mobile.png" width="auto" height="auto" loading="lazy" alt="logo"
        class="block md:hidden">

    <!--- Section10 --->
    <x-web.testimonials />
    <!--- Section10 End --->
    <!--- Section11 --->
    <section class="pt-[0px] px-[0] pb-[37px] md:pt-[44px] md:pb-[40px] bg-[#F9F9F9]">
        <div class="max-w-full px-6 md:px-[50px] mx-auto w-full md:max-w-[967px]">
            <div class="flex flex-wrap items-center justify-center">
                <div class="w-full md:w-8/12 max-w-[800px]">
                    <div class="text-center">
                        <h2 class="text-[28.177px] font-normal leading-[115.8%] text-center">Join PRIMA</h2>
                        <div class="max-w-[600px] md:max-w-[800px] mx-auto px-6 md:px-0">
                            <div class="pt-[29px] space-y-4 max-w-[400px] md:max-w-none mx-auto">
                                <div class="flex items-start gap-[7px]">
                                    <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                        loading="lazy" class="mt-1.5">
                                    <span class="text-[18px] text-left">Maximize Revenue from Prime-Time
                                        Reservations</span>
                                </div>
                                <div class="flex items-start gap-[7px]">
                                    <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                        loading="lazy" class="mt-1.5">
                                    <span class="text-[18px] text-left">Regain Control Over Your Booking System</span>
                                </div>
                                <div class="flex items-start gap-[7px]">
                                    <img src="/assets/images/icon.png" width="15" height="auto" alt="icon"
                                        loading="lazy" class="mt-1.5">
                                    <span class="text-[18px] text-left">Ensure Full Occupancy During All Service
                                        Times</span>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center gap-4 mt-8 md:flex-row">
                                <a class="w-full md:w-auto rounded-[5.199px] bg-[#5046E5] min-h-[45px] text-[14px] font-semibold text-white hover:bg-transparent hover:text-[#5046E5] flex items-center justify-center border-[1.3px] border-[#5046E5] px-12"
                                    href="#"
                                    @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                                    Talk to PRIMA →
                                </a>
                                <a class="w-full md:w-auto rounded-[5.199px] bg-transparent min-h-[45px] text-[14px] font-semibold text-[#000] hover:bg-[#000] hover:text-white flex items-center justify-center border-[1.3px] border-[#000] px-12 underline [text-underline-offset:3px]"
                                    href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">
                                    Watch Our Explainer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--- Section11 End --->
    <!--- Footer --->
    <footer class="text-white pt-[28px] px-[0] pb-[21px] md:pt-[34px] md:pb-[25px] bg-[#5046E5]">
        <div
            class="max-w-full pl-[10px] pr-[10px] mx-[auto] w-full md:max-w-[967px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <div class="text-center">
                <div class="flex items-end justify-center mt-4 text-sm text-center">
                    &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
                </div>
                <img src="/assets/images/payment_icons.png" width="auto" height="auto" loading="lazy"
                    alt="logo" class="block max-w-[173px] mx-[auto] my-[0] pt-[5px]">
            </div>
        </div>
    </footer>
    <!--- Footer End --->

    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
    @vite('resources/js/web.js')
</body>

</html>
