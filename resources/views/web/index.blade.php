<!DOCTYPE html>
<html>
<head>
    <title>PRIMA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@100..900&display=swap"
          rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>


    @filamentStyles
    @vite('resources/css/web.css')

</head>
<body x-data="{}">
<!--- header --->
<header class="sticky top-0 z-50">
    <!--- announcement bar --->
    <div class="bg-indigo-600">
        <div class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[1035px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
            <p class="text-[11px] px-[0] py-[10px] md:text-sm not-italic font-semibold text-center text-white leading-[normal] px-[0] md:py-[7px]">
                Launching in Miami, Las Vegas, New York And Europe!</p>
        </div>
    </div>
    <!--- announcement bar end --->
    <div class="border-b-[.5px] [border-bottom-color:rgba(0,_0,_0,_0.50)] bg-[#F9F9F9] relative">
        <div class="pl-[40px] pr-[65px] md:max-w-[1035px] mx-[auto] md:my-[0] md:pl-[50px] md:pr-[50px] w-full">
            <div class="flex items-center justify-between">
                <div class="max-w-[72px] md:max-w-[75px]">
                    <a href="#" class="block pl-[0] pr-[0] py-[21px] md:pl-[0] md:pr-[0] md:py-[13px]">
                        <img src="/assets/images/logo.png" width="auto" height="auto" loading="lazy" alt="logo"
                             class="block">
                    </a>
                </div>
                <div class="header_button">
                    <a href="#"
                       @click.prevent="$dispatch('open-modal', { id: 'contact' })"
                       class="leading-[normal] text-[#5249C4] rounded-[4px] border-[1px] border-[solid] border-[#5249C4]  px-[10px] py-[7px] text-[10px] md:px-[24px] md:py-[5px] md:text-[14px] [transition:all_.5s_ease] hover:bg-[#5046E5] hover:text-[#fff]  font-semibold">Talk
                        to PRIMA →</a>
                </div>
                <div class="menu_trigger_js absolute right-[20px] top-[17px] md:hidden">
                    <img src="/assets/images/icon-close.png" width="20" height="auto" loading="lazy" alt="icon-close"
                         class="block" style="display:none;">
                    <img src="/assets/images/icon-menu.png" width="25" height="auto" loading="lazy" alt="icon-menu"
                         class="block">
                </div>
                <div class="p-[30px] border-b-[.5px] border-[rgba(0,_0,_0,_0.50)] bg-[#F9F9F9] absolute top-[100%] left-[0] right-[0] md:hidden"
                     style="display:none">
                    @auth()
                        <a href="/platform/"
                           class="block text-[#5249C4] font-semibold text-[14px] leading-[normal] py-[10px]">Dashboard</a>
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
    <div class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1035px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="flex flex-wrap items-center">
            <div class="w-12/12 md:w-6/12">
                <img src="/assets/images/image1.png" width="auto" height="auto" loading="lazy" alt="logo" class="block">
            </div>
            <div class="w-12/12 md:w-6/12 md:pl-[56px]">
                <h1 class="not-italic font-normal text-[26.25px] mt-[20px] md:mt-[0px] md:text-[34.117px] leading-[100%] ">
                    PRIMA Increases Restaurant Profitability While Reducing Bots, Fake Reservations And
                    Cancellations.</h1>
                <p class="not-italic font-normal md:pt-8 pt-[15px] text-[14px] md:text-[18px]">PRIMA's Concierge Network
                    helps to fill dining rooms with the best customers at all times.</p>
                <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] md:min-h-[57.953px] text-[14px] md:text-[18.196px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5] mt-[26px] md:mt-[32px]"
                   href="#"
                   @click.prevent="$dispatch('open-modal', { id: 'contact' })">
                    Talk to PRIMA →
                </a>
                <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] md:min-h-[57.953px] text-[14px] md:text-[18.196px] not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[8px] underline [text-underline-offset:4px]"
                   href="#"
                   @click.prevent="$dispatch('open-modal', { id: 'video' })"
                >Watch Our Explainer</a>
                <p class="text-[14px] md:text-[18.196px] not-italic font-normal leading-[normal] mt-[22px] md:mt-[29px] text-center">
                    <span class="text-[#DE6520]">★★★★★</span> Rated 5/5 stars by our <a href="#"
                                                                                        class="underline [text-underline-offset:4px]">partners</a>
                </p>
            </div>
        </div>
    </div>
</section>
<!--- Section1 End --->
<!--- Section2 --->
<section class="pt-[20px] px-[0] pb-[18px] md:pt-[30px] md:pb-[48px]">
    <div class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full  md:my-[0] md:pl-[50px] md:pr-[50px] md:max-w-[1043px]">
        <h2 class="not-italic font-normal text-center text-[36px] leading-[normal]">Supported By:</h2>
        <!--- Partner Image --->
        <div class="pt-[11px]">
            <img src="/assets/images/partner-image.png" width="auto" height="auto" loading="lazy" alt="logo"
                 class="hidden md:block">
            <img src="/assets/images/partner_image_mobile.png" width="auto" height="auto" loading="lazy" alt="logo"
                 class="block md:hidden mt-[43px]">
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
                <div class="text-[14px] font-medium leading-[normal] text-center rounded-[15.157px] bg-[#818181] px-[10px] py-[5px] text-[#fff]">
                    THE PROBLEM
                </div>
                <h2 class="text-[23.25px] leading-[100%] mt-[30px]">Many Top Restaurants Are Fully Booked, Sometimes
                    Months In Advance!</h2>
                <p class="text-[15px] leading-[normal] mt-[20px]">Affluent consumers cannot gain access to these
                    restaurants, and bots have overtaken reservation systems creating fake bookings only to re-sell them
                    on the grey market for a profit.
                </p>
                <h3 class="text-[20.25px] not-italic mt-[12px]">This is NOT the way it should be.</h3>
            </div>
            <div class="md:pl-[75px] md:max-w-[50%] pt-[47px] md:pt-[0px]">
                <div class="text-[14px] font-medium leading-[normal] text-center rounded-[15.157px] bg-[#5046E5] px-[10px] py-[5px] text-[#fff]">
                    INTRODUCING PRIMA VIP
                </div>
                <h2 class="text-[23.25px] leading-[100%] mt-[30px]">PRIMA allows in-demand venue to take control over
                    the sale of their primetime reservations... </h2>
                <p class="text-[15px] leading-[normal] mt-[20px]">Reservations are sold through a network of vetted and
                    reputable hospitality concierges, eliminating bots, fake reservations and last minute cancellations.
                    All while helping fill dining rooms during non-peak hours.</p>
            </div>
        </div>
    </div>
</section>
<!--- Section3 End --->
<img src="/assets/images/image5.png" width="auto" height="auto" loading="lazy" alt="logo" class="block md:hidden">
<!--- Section4 --->
<section class="pt-[50px] md:pt-[30px] px-[0] pb-[20px] md:pb-[50px]">
    <div class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[1035px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="pb-[21px] md:pb-[53px] text-center">
            <div class="px-[34px] py-[5px] rounded-[15.157px] bg-[#5046E5] font-medium text-center inline-flex  text-white text-[14px]">
                Why CHOOSE PRIMA?
            </div>
            <h2 class="text-[30.25px] leading-[115.8%] max-w-[460px] mx-[auto] my-[0] pt-[24px]">Revolutionize Your
                Reservation System with PRIMA</h2>
        </div>
        <div class="hidden md:block">
            <div>
                <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                    <!--- Section4 Item --->
                    <div class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon1.png" width="33" height="auto" alt="icon1" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Maximize Revenue from Prime-Time
                            Reservations</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Directly sell your
                            prime-time tables to high-value clients through PRIMA, ensuring maximum revenue from each
                            booking and eliminating losses to the grey market.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon2.png" width="33" height="auto" alt="icon2" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Real-Time Reservation Management</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Manage your
                            reservations with real-time updates, reducing the risk of over-bookings and ensuring optimal
                            table occupancy at all times.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon3.png" width="33" height="auto" alt="icon3" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Automated Concierge Commissions</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Automate commission
                            calculations and payments, making it easy for concierges to earn and for restaurants to
                            manage payouts efficiently.</p>
                    </div>
                    <!--- Section4 Item End --->
                </div>
            </div>
            <div class="md:pt-[41px]">
                <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                    <!--- Section4 Item --->
                    <div class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon4.png" width="33" height="auto" alt="icon4" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Attract High-Value Clients</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Offer exclusive
                            reservations to high-value clients who are willing to pay a premium, elevating your
                            restaurant’s status and attracting discerning diners.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon5.png" width="33" height="auto" alt="icon5" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Comprehensive Analytics and Insights</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Access detailed
                            analytics on reservations, revenue, and customer satisfaction, helping you make informed
                            decisions to enhance your service and profitability.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon6.png" width="33" height="auto" alt="icon6" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Support Ethical Practices and Community
                            Impact</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Participate in ethical
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
                    <div class="min-h-[280px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon1.png" width="33" height="auto" alt="icon1" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Maximize Revenue from Prime-Time
                            Reservations</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Directly sell your
                            prime-time tables to high-value clients through PRIMA, ensuring maximum revenue from each
                            booking and eliminating losses to the grey market.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="min-h-[288px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon2.png" width="33" height="auto" alt="icon2" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Real-Time Reservation Management</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Manage your
                            reservations with real-time updates, reducing the risk of over-bookings and ensuring optimal
                            table occupancy at all times.</p>
                    </div>
                    <!--- Section4 Item End --->
                </div>
            </div>

            <div class="px-[5px] py-[0]">
                <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                    <!--- Section4 Item --->
                    <div class="min-h-[280px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon3.png" width="33" height="auto" alt="icon3" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Automated Concierge Commissions</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Automate commission
                            calculations and payments, making it easy for concierges to earn and for restaurants to
                            manage payouts efficiently.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="min-h-[288px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon4.png" width="33" height="auto" alt="icon4" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Attract High-Value Clients</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Offer exclusive
                            reservations to high-value clients who are willing to pay a premium, elevating your
                            restaurant’s status and attracting discerning diners.</p>
                    </div>
                    <!--- Section4 Item End --->
                </div>
            </div>
            <div class="px-[5px] py-[0]">
                <div class="flex items-stretch flex-wrap gap-x-[37px] gap-y-[24px]">
                    <!--- Section4 Item --->
                    <div class="min-h-[280px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon5.png" width="33" height="auto" alt="icon5" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Comprehensive Analytics and Insights</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Access detailed
                            analytics on reservations, revenue, and customer satisfaction, helping you make informed
                            decisions to enhance your service and profitability.</p>
                    </div>
                    <!--- Section4 Item End --->
                    <!--- Section4 Item --->
                    <div class="min-h-[288px] pt-[16px] px-[27px] pb-[29px] md:max-w-[30%] md:px-[27px] md:py-[16px] rounded-[5px] border-[0.3px] border-[solid] border-[#000]">
                        <img src="/assets/images/icon6.png" width="33" height="auto" alt="icon6" loading="lazy">
                        <h3 class="pt-[11px] text-[18.25px] leading-[normal]">Support Ethical Practices and Community
                            Impact</h3>
                        <p class="pt-[16px] md:pt-[13px] text-[16px] font-light leading-[normal]">Participate in ethical
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
    <div class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[820px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="flex flex-col-reverse md:flex-row flex-wrap">
            <div class="section5_image md:max-w-[50%] w-full">
                <img src="/assets/images/image2.png" width="auto" height="auto" loading="lazy" alt="logo"
                     class="block max-w-[239px] mx-[auto] my-[0] md:max-w-full">
            </div>
            <div class="section5_text_main md:max-w-[50%] md:pl-[50px] w-full pb-[50px] md:pb-[0px]">
                <h2 class="text-[26.34px] leading-[115.8%] hidden md:block">No more bots and no more last minute
                    cancellations.</h2>
                <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5] md:mt-[26px] md:mt-[34px]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'contact' })">Talk to PRIMA →</a>
                <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[8px] underline [text-underline-offset:3px]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">Watch Our Explainer</a>
                <p class="text-[14px] not-italic font-normal leading-[normal] mt-[20px] text-center">
                    <span class="text-[#DE6520]">★★★★★</span> Rated 5/5 stars by our <a href="#"
                                                                                        class="underline [text-underline-offset:4px]">partners</a>
                </p>
            </div>
        </div>
    </div>
</section>
<!--- Section5 End --->
<!--- Section6 --->
<section class="pt-[51px] px-[0] pb-[100px] md:pt-[65px] md:pb-[81px]">
    <div class="max-w-full pl-[30px] pr-[30px] mx-[auto] w-full md:max-w-[1244px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="pb-[45px] md:pb-[65px] text-center">
            <h2 class="text-[30.25px] leading-[115.8%]">How PRIMA Works</h2>
            <p class="text-[17px] leading-[normal] max-w-[248px] mx-[auto] my-[0] pt-[12px]">A Seamless Solution for
                Restaurants and Concierges</p>
        </div>
        <div class="flex items-stretch flex-wrap">
            <!--- Section6 Item --->
            <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                <div class="border-t-[unset] border-l w-px h-full left-[18px] top-[20px] w-full block md:border-l-0 absolute md:left-2/4 md:top-[18px] md:border-t border-dashed border-[#000] md:border-t-[#000] md:w-full"></div>
                <div class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                    1
                </div>
                <h3 class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">Join
                    PRIMA</h3>
                <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">Venues
                    join PRIMA and define their booking hours and PRIME and NON-PRIME dining times.</p>
            </div>
            <!--- Section6 Item End --->
            <!--- Section6 Item --->
            <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                <div class="border-t-[unset] border-l w-px h-full left-[18px] top-[20px] w-full block md:border-l-0 absolute md:left-2/4 md:top-[18px] md:border-t border-dashed border-[#000] md:border-t-[#000] md:w-full"></div>
                <div class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                    2
                </div>
                <h3 class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">
                    Choose Restaurant</h3>
                <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">
                    Concierges across the PRIMA Network have access to the PRIMA Availability Calendar</p>
            </div>
            <!--- Section6 Item End --->
            <!--- Section6 Item --->
            <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                <div class="border-t-[unset] border-l w-px h-full left-[18px] top-[20px] w-full block md:border-l-0 absolute md:left-2/4 md:top-[18px] md:border-t border-dashed border-[#000] md:border-t-[#000] md:w-full"></div>
                <div class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                    3
                </div>
                <h3 class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">Book
                    through Concierge Network</h3>
                <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">
                    Prime-Time Reservations are available for sale. Non-Prime Reservations are booked without fees.</p>
            </div>
            <!--- Section6 Item End --->
            <!--- Section6 Item --->
            <div class="md:w-3/12 w-full md:text-center relative pl-[59px] md:pl-[0px] pb-[45px] md:pb-[0px]">
                <div class="w-[37px] h-[37px] bg-[#5046E5] text-[23.792px] leading-[normal] rounded-[100%] inline-flex items-center justify-center not-italic font-normal text-white absolute left-[0] top-[0] md:relative z-10 ">
                    4
                </div>
                <h3 class="text-[18.25px] leading-[normal] md:max-w-[170px] mx-[auto] my-[0] pt-[5px] md:pt-[13px]">
                    Real-Time Reporting</h3>
                <p class="text-[16px] leading-[normal] md:max-w-[240px] mx-[auto] my-[0] pt-[17px] font-light">All
                    revenues and reservations are tracked in real time.</p>
            </div>
            <!--- Section6 Item End --->
        </div>
        <div class="max-w-[630px] mx-[auto] my-[0] pt-[38px] md:pt-[82px] text-center">
            <div class="flex flex-wrap md:flex-nowrap md:gap-x-[17px] md:flex-row-reverse">
                <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'contact' })">Talk to PRIMA →</a>
                <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[6px] md:mt-[0px] underline [text-underline-offset:3px]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">Watch Our Explainer</a>
            </div>
            <p class="text-[14px] not-italic font-normal leading-[normal] mt-[14px] md:mt-[28px] text-center">
                <span class="text-[#DE6520]">★★★★★</span> Rated 5/5 stars by our <a href="#"
                                                                                    class="underline [text-underline-offset:4px]">partners</a>
            </p>
        </div>
    </div>
</section>
<!--- Section6 End --->
<div class="max-w-[262px] md:max-w-[1048.223px] h-[0.5px] bg-[#000]  mx-[auto] w-full  md:my-[0]"></div>
<!--- Section7 --->
<section class="pt-[33px] px-[0] pb-[30px] md:pt-[61px] md:pb-[105px]">
    <div class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1148px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="flex items-stretch flex-wrap">
            <!--- Section7 Left --->
            <div class="md:border-r border-[#000] md:max-w-[50%] w-full text-center relative">
                <img src="/assets/images/image3.png" width="auto" height="auto" loading="lazy" alt="logo"
                     class="block hidden md:block max-w-[265px] mx-[auto] my-[0]">
                <h3 class="text-[30.25px] leading-[115.8%] max-w-[305px] mx-[auto] my-[0] md:pt-[25px]">With PRIMA,
                    Everybody Wins™</h3>
                <p class="text-[16px] leading-[normal] max-w-[308px] mx-[auto] my-[0] pt-[29px]">
                     <span class="flex items-baseline gap-[7px] text-left">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"> Restaurants maximize their revenue. </span>
                    <span class="flex items-baseline gap-[7px] text-left mt-[20px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"> Concierges earn additional income. </span>
                    <span class="flex items-baseline gap-[7px] text-left mt-[20px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"> Diners enjoy their favorite restaurants at their chosen time. </span>
                </p>
                <p class="text-[24px] font-light leading-[normal] md:px-[15px] py-[5px] border-[1px] border-[solid] border-[#000] mt-[31px] md:inline-block">
                    It's a win-win-win for all!</p>
            </div>
            <!--- Section7 Left End --->
            <!--- Section7 Right --->
            <div class="md:max-w-[50%] md:pl-[54px] pt-[51px] md:pt-[0px] w-full text-center relative">
                <div class="rounded-[15.157px] bg-[#5046E5] px-[30px] py-[6px]  font-medium text-center inline-flex  text-white">
                    Where Money Goes
                </div>
                <h3 class="pt-[37px] text-[30.25px] leading-[115.8%] max-w-[250px] mx-[auto] my-[0] md:max-w-full">PRIMA
                    vs. Grey Market</h3>
                <div class="pt-[36px] md:pt-[49px] flex">
                    <div class="w-6/12">
                        <h4 class="text-[21px] leading-[normal]">OLD WAY</h4>
                        <p class="pt-[29px] text-[15.67px] font-light leading-[normal]">Grey Market Fee</p>
                        <p class="text-[15.67px] font-medium leading-[normal] pt-[10px]">$200</p>
                        <div class="gap-[6px] mt-[17px] flex flex-col">
                            <div class="h-[284px] bg-[#ABABAB] gap-[26px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                <div class="text-[12.71px] font-medium leading-[normal]">Grey Market Middlemen</div>
                                <div class="text-[12.71px] font-medium leading-[normal]">80%</div>
                            </div>
                            <div class="h-[65px] bg-[#818181] gap-[10px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                <div class="text-[12.71px] font-medium leading-[normal]">Other Fees</div>
                                <div class="text-[12.71px] font-medium leading-[normal]">20%</div>
                            </div>
                        </div>
                    </div>
                    <div class="w-6/12">
                        <h4 class="text-[21px] leading-[normal] font-bold font-[Inter] text-[#5046E5]">PRIMA</h4>
                        <p class="pt-[29px] text-[15.67px] font-light leading-[normal]">Reservation Fee</p>
                        <p class="text-[15.67px] font-medium leading-[normal] pt-[10px]">$200</p>
                        <div class="gap-[6px] mt-[17px] flex flex-col">
                            <div class="h-[182px] bg-indigo-300 gap-[27px] pb-[50px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                <div class="text-[12.71px] font-medium leading-[normal]">Restaurant Commission</div>
                                <div class="text-[12.71px] font-medium leading-[normal]">60%</div>
                            </div>
                            <div class="bg-indigo-400 h-[65px] gap-[4px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                <div class="text-[12.71px] font-medium leading-[normal]">Concierge Service</div>
                                <div class="text-[12.71px] font-medium leading-[normal]">10-15%</div>
                            </div>
                            <div class="h-[75px] text-white bg-indigo-600 gap-[2px] pb-[10px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex flex-col items-center justify-center">
                                <div class="text-[12.71px] font-medium leading-[normal]">PRIMA</div>
                            </div>
                            <div class="bg-indigo-700 text-white h-[25px] flex-row gap-[7px] -mt-[6px] px-[15px] py-[0] max-w-[117px] mx-[auto] my-[0] w-full inline-flex items-center justify-center">
                                <div class="text-[12.71px] font-medium leading-[normal]">CHARITY</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--- Section7 Right End --->
        </div>
        <div class="max-w-[630px] mx-[auto] my-[0] pt-[34px] md:pt-[82px] text-center">
            <h2 class="text-[28.177px] leading-[115.8%] pb-[20px] md:pb-[37px]">PRIMA provides direct, bottom-line
                revenue and profits to each participating restaurant.</h2>
            <div class="flex flex-wrap md:flex-nowrap md:gap-x-[17px] md:flex-row-reverse">
                <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'contact' })">Talk to PRIMA →</a>
                <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px] text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[6px] md:mt-[0px] underline [text-underline-offset:3px]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">Watch Our Explainer</a>
            </div>
        </div>
    </div>
</section>
<!--- Section7 End --->
<div class="max-w-[262px] md:max-w-[1048.223px] h-[0.5px] bg-[#000]  mx-[auto] w-full  md:my-[0]"></div>
<!--- Section8 --->
<section class="pt-[25px] px-[0] pb-[40px] md:pt-[61px] md:pb-[99px]">
    <div class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1170px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="pb-[55px] text-center">
            <p class="text-[14px] leading-[normal] text-center">
                <span class="text-[#DE6520]">★★★★★</span> Rated 5/5 stars by our <a href="#"
                                                                                    class="underline [text-underline-offset:4px]">partners</a>
            </p>
            <h2 class="text-[28.177px] leading-[115.8%] pt-[16px]">Transforming the Hospitality Industry</h2>
            <p class="text-[16px] not-italic font-normal leading-[normal] pt-[25px] max-w-[590px] mx-[auto] my-[0]">
                PRIMA not only enhances the reservation process - our platform ensures fair practices, supports local
                businesses, and contributes to social causes. <strong class="block pt-[20px]">Here's how we make a
                    difference for:</strong>
            </p>
        </div>
        <div class="md:hidden flex gap-[6px]">
            <h3 class="text-[rgba(0,_0,_0,_0.50)] text-[16.07px] px-[0] py-[11px] max-w-[calc(33.33% -(6px)* 2 / 3)] bg-[#F4F4F4] border-[.34px] border-[solid] border-[rgba(0,0,0,0.50)] [transition:all_.5s_ease] w-full text-center section8_js_trigger active"
                data_tab="1">Diners</h3>
            <h3 class="text-[rgba(0,_0,_0,_0.50)] text-[16.07px] px-[0] py-[11px] max-w-[calc(33.33% -(6px)* 2 / 3)] bg-[#F4F4F4] border-[.34px] border-[solid] border-[rgba(0,0,0,0.50)] [transition:all_.5s_ease] w-full text-center section8_js_trigger"
                data_tab="2">Restaurants</h3>
            <h3 class="text-[rgba(0,_0,_0,_0.50)] text-[16.07px] px-[0] py-[11px] max-w-[calc(33.33% -(6px)* 2 / 3)] bg-[#F4F4F4] border-[.34px] border-[solid] border-[rgba(0,0,0,0.50)] [transition:all_.5s_ease] w-full text-center section8_js_trigger"
                data_tab="3">Concierges</h3>
        </div>
        <div class="flex items-stretch flex-wrap section8_slider gap-[53px] flex-nowrap">
            <!--- Section8 Item --->
            <div class="section8_js_trigger_item border-[0.3px] border-[solid] border-[rgba(0,0,0,0.30)] bg-[rgba(255,_255,_255,_0.30)] [box-shadow:2px_4px_4px_0px_rgba(0,_0,_0,_0.10)] mt-[7px] md:mt-[48px] px-[14px] py-[0] w-full md:w-4/12 pb-[29px]  text-center relative active"
                 data_tab="1">
                <h3 class="text-[26.07px] font-semibold leading-[normal] font-[Inter] absolute -top-[47px] left-[0] right-[0] p-[4px] bg-[#5046E5] hidden md:block text-white">
                    Diners</h3>
                <h3 class="pt-[29px] text-[25.18px] font-normal leading-[115.8%]">Exclusive Dining Experiences Made
                    Easy</h3>
                <p class="pt-[20px] text-[16px] font-light leading-[normal]">With PRIMA, you can secure prime-time
                    tables at the best venues effortlessly. </p>
                <p class="pt-[18px] max-w-full text-[16px] leading-[normal] mx-[auto] my-[0]">
                     <span class="block pl-[21px] relative text-left">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Guaranteed Reservations</strong> Secure tables at fully booked, high-demand restaurants </span>
                    <span class="block pl-[21px] relative text-left mt-[21px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Convenience</strong> Effortless booking process through our user-friendly platform. </span>
                    <span class="block pl-[21px] relative text-left mt-[21px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Exclusive Access</strong> Access prime-time reservations and unique dining experiences. </span>
                </p>
            </div>
            <!--- Section8 Item End --->
            <!--- Section8 Item --->
            <div class="section8_js_trigger_item border-[0.3px] border-[solid] border-[rgba(0,0,0,0.30)] bg-[rgba(255,_255,_255,_0.30)] [box-shadow:2px_4px_4px_0px_rgba(0,_0,_0,_0.10)] mt-[7px] md:mt-[48px] px-[14px] py-[0] w-full md:w-4/12 text-center relative"
                 data_tab="2">
                <h3 class="text-[26.07px] font-semibold leading-[normal] font-[Inter] absolute -top-[47px] left-[0] right-[0] p-[4px] bg-[#5046E5] hidden md:block text-white">
                    Restaurants</h3>
                <h3 class="pt-[29px] text-[25.18px] font-normal leading-[115.8%]">Maximize Profits and Control Your
                    Reservations</h3>
                <p class="pt-[20px] text-[16px] font-light leading-[normal]">Eliminate the grey market, regain control
                    over your reservation book, and ensure that your tables are always filled.</p>
                <p class="pt-[18px] max-w-full text-[16px] leading-[normal] mx-[auto] my-[0]">
                     <span class="block pl-[21px] relative text-left">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Increased Revenue</strong> Sell prime-time reservations directly to diners willing to pay a premium </span>
                    <span class="block pl-[21px] relative text-left mt-[21px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Control</strong> Regain control over your reservation book and eliminate third-party middlemen. </span>
                    <span class="block pl-[21px] relative text-left mt-[21px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Fill Non-Prime Slots</strong> Incentivize concierges to book non-prime time slots, ensuring full occupancy. </span>
                </p>
                <p>
                    <a href="#"
                       class="inline-block mt-[32px] underline text-[16px] font-medium leading-[120%] mb-[30px] [text-underline-offset:2px]">LEARN
                        MORE</a>
                </p>
            </div>
            <!--- Section8 Item End --->
            <!--- Section8 Item --->
            <div class="section8_js_trigger_item border-[0.3px] border-[solid] border-[rgba(0,0,0,0.30)] bg-[rgba(255,_255,_255,_0.30)] [box-shadow:2px_4px_4px_0px_rgba(0,_0,_0,_0.10)] mt-[7px] md:mt-[48px] px-[14px] py-[0] w-full md:w-4/12 text-center relative"
                 data_tab="3">
                <h3 class="text-[26.07px] font-semibold leading-[normal] font-[Inter] absolute -top-[47px] left-[0] right-[0] p-[4px] bg-[#5046E5] hidden md:block text-white">
                    Concierges</h3>
                <h3 class="pt-[29px] text-[25.18px] font-normal leading-[115.8%]">Enhance Your Service and Earn
                    Commissions</h3>
                <p class="pt-[20px] text-[16px] font-light leading-[normal]">Earn attractive commissions by securing
                    reservations for high-demand restaurants.</p>
                <p class="pt-[18px] max-w-full text-[16px] leading-[normal] mx-[auto] my-[0]">
                     <span class="block pl-[21px] relative text-left">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Earnings</strong> Earn commissions on every reservation booked through the platform. </span>
                    <span class="block pl-[21px] relative text-left mt-[21px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Professional Network</strong> Be part of a prestigious network of concierges and hospitality professionals. </span>
                    <span class="block pl-[21px] relative text-left mt-[21px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"
                          class="absolute left-[0] top-[3px]">
                     <strong class='text-[19px] not-italic font-normal leading-[120.9%] font-["DM_Serif_Display"] block pb-[6px]'>Client Satisfaction</strong> Provide your clients with exclusive access to top dining experiences. </span>
                </p>
                <p>
                    <a href="#"
                       class="inline-block mt-[32px] underline text-[16px] font-medium leading-[120%] mb-[30px] [text-underline-offset:2px]">LEARN
                        MORE</a>
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
<section class="pt-[31px] px-[0] pb-[41px] md:pt-[75px] md:pb-[68px]">
    <div class="max-w-full pl-[20px] pr-[20px] mx-[auto] w-full md:max-w-[1440px]  md:my-[0] md:pl-[0px] md:pr-[0px]">
        <div class="pb-[34px] text-center">
            <p class="text-[14px] leading-[normal] text-center">
                <span class="text-[#DE6520]">★★★★★</span> Rated 5/5 stars by our <a href="#"
                                                                                    class="underline [text-underline-offset:4px]">partners</a>
            </p>
            <h2 class="text-[28.177px] leading-[115.8%] pt-[34px]">What Our Partners Say</h2>
            <p class="text-[14px] leading-[normal] pt-[17px]">Success Stories from Restaurants & Concierges</p>
        </div>
        <div class="section10_slider_js">
            <!--- Section10 Item --->
            <div class="text-center relative px-[4px] py-[0] h-auto">
                <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]
                     ">
                    <h3 class="text-[18px] font-medium leading-[normal] font-[Inter]">"PRIMA has revolutionized our
                        reservation system."</h3>
                    <p class="text-[16px] font-light leading-[normal] pt-[36px]">We now fill prime-time tables
                        effortlessly and have seen a significant increase in profits.</p>
                    <p class="text-[16px] font-normal leading-[normal] pt-[49px]">Nikolas E. - Restaurant Owner</p>
                    <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">★★★★★</p>
                </div>
            </div>
            <!--- Section10 Item End --->
            <!--- Section10 Item --->
            <div class="text-center relative px-[4px] py-[0] h-auto">
                <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]
                     ">
                    <h3 class="text-[18px] font-medium leading-[normal] font-[Inter]">"PRIMA has revolutionized our
                        reservation system."</h3>
                    <p class="text-[16px] font-light leading-[normal] pt-[36px]">We now fill prime-time tables
                        effortlessly and have seen a significant increase in profits.</p>
                    <p class="text-[16px] font-normal leading-[normal] pt-[49px]">Nikolas E. - Restaurant Owner</p>
                    <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">★★★★★</p>
                </div>
            </div>
            <!--- Section10 Item End --->
            <!--- Section10 Item --->
            <div class="text-center relative px-[4px] py-[0] h-auto">
                <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]
                     ">
                    <h3 class="text-[18px] font-medium leading-[normal] font-[Inter]">"PRIMA has revolutionized our
                        reservation system."</h3>
                    <p class="text-[16px] font-light leading-[normal] pt-[36px]">We now fill prime-time tables
                        effortlessly and have seen a significant increase in profits.</p>
                    <p class="text-[16px] font-normal leading-[normal] pt-[49px]">Nikolas E. - Restaurant Owner</p>
                    <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">★★★★★</p>
                </div>
            </div>
            <!--- Section10 Item End --->
            <!--- Section10 Item --->
            <div class="text-center relative px-[4px] py-[0] h-auto">
                <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]
                     ">
                    <h3 class="text-[18px] font-medium leading-[normal] font-[Inter]">"PRIMA has revolutionized our
                        reservation system."</h3>
                    <p class="text-[16px] font-light leading-[normal] pt-[36px]">We now fill prime-time tables
                        effortlessly and have seen a significant increase in profits.</p>
                    <p class="text-[16px] font-normal leading-[normal] pt-[49px]">Nikolas E. - Restaurant Owner</p>
                    <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">★★★★★</p>
                </div>
            </div>
            <!--- Section10 Item End --->
            <!--- Section10 Item --->
            <div class="text-center relative px-[4px] py-[0] h-auto">
                <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]
                     ">
                    <h3 class="text-[18px] font-medium leading-[normal] font-[Inter]">"PRIMA has revolutionized our
                        reservation system."</h3>
                    <p class="text-[16px] font-light leading-[normal] pt-[36px]">We now fill prime-time tables
                        effortlessly and have seen a significant increase in profits.</p>
                    <p class="text-[16px] font-normal leading-[normal] pt-[49px]">Nikolas E. - Restaurant Owner</p>
                    <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">★★★★★</p>
                </div>
            </div>
            <!--- Section10 Item End --->
            <!--- Section10 Item --->
            <div class="text-center relative px-[4px] py-[0] h-auto">
                <div class="rounded-[5px] border-[0.3px] border-[solid] border-[#000] bg-[#FFF] pt-[37px] px-[14px] pb-[26px] rounded-[5px]
                     ">
                    <h3 class="text-[18px] font-medium leading-[normal] font-[Inter]">"PRIMA has revolutionized our
                        reservation system."</h3>
                    <p class="text-[16px] font-light leading-[normal] pt-[36px]">We now fill prime-time tables
                        effortlessly and have seen a significant increase in profits.</p>
                    <p class="text-[16px] font-normal leading-[normal] pt-[49px]">Nikolas E. - Restaurant Owner</p>
                    <p class="pt-[14px] text-[#DE6520] text-[20.193px] leading-[normal]">★★★★★</p>
                </div>
            </div>
            <!--- Section10 Item End --->
        </div>
    </div>
</section>
<!--- Section10 End --->
<!--- Section11 --->
<section class="pt-[0px] px-[0] pb-[37px] md:pt-[44px] md:pb-[40px] bg-[#F9F9F9]">
    <div class="max-w-full pl-[00px] pr-[00px] mx-[auto] w-full md:max-w-[967px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="flex items-center flex-wrap">
            <div class="w-full md:w-7/12">
                <img src="/assets/images/image5.png" width="auto" height="auto" loading="lazy" alt="logo" class="block">
            </div>
            <div class="w-full pt-[30px] pl-[30px] pr-[30px] md:pt-[0px] md:pr-[0px] md:pl-[63px] md:w-5/12">
                <h2 class="text-[28.177px] font-normal leading-[115.8%] text-center md:text-left">Join PRIMA</h2>
                <p class="text-[14px] not-italic font-normal leading-[normal] mt-[22px]  text-center md:text-left">
                    <span class="text-[#DE6520]">★★★★★</span> Rated 5/5 stars by our <a href="#"
                                                                                        class="underline [text-underline-offset:4px]">partners</a>
                </p>
                <p class="text-[16px] leading-[normal] pt-[29px]">
                     <span class="text-[18px] items-center flex gap-[7px] text-left">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"> Maximize Revenue from Prime-Time Reservations </span>
                    <span class="text-[18px] items-center flex gap-[7px] text-left mt-[15px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"> Regain Control Over Your Booking System </span>
                    <span class="text-[18px] items-center flex gap-[7px] text-left mt-[15px]">
                     <img src="/assets/images/icon.png" width="15" height="auto" alt="icon" loading="lazy"> Ensure Full Occupancy During All Service Times </span>
                </p>
                <a class="rounded-[5.199px] bg-[#5046E5] w-full min-h-[45px] text-[14px] not-italic font-semibold leading-[normal] text-[#FFF] [transition:all_.5s_ease] hover:background: transparent hover:bg-transparent hover:text-[#5046E5] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#5046E5] mt-[26px] md:mt-[34px]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'contact' })">Talk to PRIMA →</a>
                <a class="rounded-[5.199px] bg-transparent w-full min-h-[45px]  text-[14px]  not-italic font-semibold leading-[normal] text-[#000] [transition:all_.5s_ease] hover:background: transparent hover:bg-[#000] hover:text-[#fff] inline-flex items-center justify-center border-[1.3px] border-[solid] border-[#000] mt-[8px] underline [text-underline-offset:3px]"
                   href="#" @click.prevent="$dispatch('open-modal', { id: 'video' })">Watch Our Explainer</a>
            </div>
        </div>
    </div>
</section>
<!--- Section11 End --->
<!--- Footer --->
<footer class="text-white pt-[28px] px-[0] pb-[21px] md:pt-[34px] md:pb-[25px] bg-[#5046E5]">
    <div class="max-w-full pl-[10px] pr-[10px] mx-[auto] w-full md:max-w-[967px]  md:my-[0] md:pl-[50px] md:pr-[50px]">
        <div class="text-center">
            <div class="mt-4 flex items-end justify-center text-center text-sm">
                &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
            </div>
            <img src="/assets/images/payment_icons.png" width="auto" height="auto" loading="lazy" alt="logo"
                 class="block max-w-[173px] mx-[auto] my-[0] pt-[5px]">
        </div>
    </div>
</footer>
<!--- Footer End --->
<x-filament::modal id="contact" width="2xl">
    <x-slot name="heading">
        Talk to PRIMA
    </x-slot>
    Contact
</x-filament::modal>

<x-filament::modal id="video" width="2xl">
    <x-slot name="heading">
        Watch Our Explainer
    </x-slot>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/pxyHz-RjHW0?si=Ce3xU7A0J3Fg7WR6"
            class="youtube-video"
            title="YouTube video player" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
</x-filament::modal>
@livewire('notifications')
@filamentScripts
@vite('resources/js/app.js')
@vite('resources/js/web.js')
</body>
</html>
