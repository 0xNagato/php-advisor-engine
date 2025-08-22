@extends('site.layout')

@section('title', 'Restaurants - PRIMA')

@section('lead-form')
<!-- Header Lead Form -->
<div id="panelHeader" class="collapsible">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 border-t border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold">Tell us a bit about you</h3>
            <button type="button" class="close-btn text-slate-500 hover:text-slate-700" data-close="panelHeader"
                    aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form class="mt-3 grid grid-cols-2 gap-3" onsubmit="event.preventDefault(); alert('Lead Submitted');">
            <label class="col-span-2 sm:col-span-1">
                <span class="sr-only">I am a</span>
                <select required name="persona" class="w-full px-3 py-2 rounded-xl border border-slate-300">
                    <option value="">I am a…</option>
                    <option value="hotel">Hotel / Property</option>
                    <option value="concierge">Concierge</option>
                    <option value="restaurant">Restaurant</option>
                    <option value="creator">Creator / Influencer</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <input required name="fullName" placeholder="Full Name"
                   class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
            <input name="company" placeholder="Company / Property"
                   class="col-span-2 px-3 py-2 rounded-xl border border-slate-300" />
            <input required type="email" name="email" placeholder="Email"
                   class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
            <input name="phone" placeholder="Phone"
                   class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
            <input name="city" placeholder="City"
                   class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
            <input name="preferredTime" placeholder="Preferred Contact Time"
                   class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
            <textarea name="notes" rows="3" placeholder="Anything else we should know?"
                      class="col-span-2 px-3 py-2 rounded-xl border border-slate-300"></textarea>
            <div class="col-span-2 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Submit</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('content')
<!-- HERO -->
<section id="hero" class="relative py-10">
    <div class="splash"></div>
    <div class="max-w-5xl mx-auto px-4 md:px-6 relative z-10">
        <div class="flex flex-col text-center wow animate__animated animate__zoomIn">
            <h1 class="text-[34px] md:text-[65px] leading-[1] font-black">Turn <span
                    class="text-indigo-600">High‑Intent</span> Guests Into Predictable <span
                    class="text-indigo-600">Revenue</span>
            </h1>
            <p class="mt-7 text-xl">PRIMA fills seats with travelers and locals already in market and ready to book. Measure
                every channel and get paid on sold‑out demand.</p>
            <div
                    class="max-w-4xl mx-auto rounded-2xl items-start bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 mt-7 text-[18px] md:text-[20px] space-y-1 text-left">
                <div>
                    <label class="inline-flex justify-start items-start text-left space-x-3 text-xl">
                        <span class="flex justify-center p-1 rounded-full border-2 border-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 data-lucide="check" class="lucide lucide-check w-4 h-4 text-indigo-600">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg>
                        </span>
                        <span><strong>No Invoicing.</strong> Automated attribution and remittance. No chasing down
                            payments.</span></label>
                </div>
                <div><label class="inline-flex items-start space-x-3 text-xl"><span
                            class="flex justify-center p-1 rounded-full border-2 border-indigo-600"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                data-lucide="check" class="lucide lucide-check w-4 h-4 text-indigo-600">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg></span><span><strong>No Collections.</strong> PRIMA settles fees on completed covers and credits
                            the right partner.</span></label></div>
                <div><label class="inline-flex items-start space-x-3 text-xl"><span
                            class="flex justify-center p-1 rounded-full border-2 border-indigo-600"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                data-lucide="check" class="lucide lucide-check w-4 h-4 text-indigo-600">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg></span><span><strong>Predictable Payouts.</strong> Twice‑monthly deposits with line‑item
                            detail.</span></label></div>
            </div>
        </div>
    </div>
</section>

<!-- LIVE CITIES -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="relative flex items-center justify-center mt-10 wow animate__animated animate__zoomIn">
        <!-- Heading above the curve -->
        <h2 class="relative z-10 bg-white px-4 text-3xl font-semibold text-center mb-6">
            <span class="relative text-center z-10">
                PRIMA is
                <span class="relative inline-block">
                    <span class="inline-block relative z-10">LIVE</span>
                    <span class="absolute left-0 right-0 bottom-0 h-5 z-0 pointer-events-none"
                          style="background: url('{{ asset('images/site/scribble-underline.svg') }}') no-repeat center bottom; background-size: contain; width: 100%;"></span>
                </span>
                in
            </span>
        </h2>
    </div>

    <div class="rounded-2xl overflow-hidden shadow-md border border-slate-300">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-[1px]">
            <a href="https://book.primaapp.com/miami" target="_blank"
               class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInLeft"
               style="background-image: url('{{ asset('images/site/miami.jpg') }}'); background-size: cover; background-position: center;">
                <div class="absolute inset-0 bg-black opacity-35 group-hover:opacity-70 pointer-events-none transition-opacity duration-300"></div>
                <div class="relative z-10 flex items-start justify-between">
                    <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
                    <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
                </div>
                <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Miami</div>
            </a>
            <a href="https://book.primaapp.com/los-angeles" target="_blank"
               class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInUp"
               style="background-image: url('{{ asset('images/site/los-angeles.jpg') }}'); background-size: cover; background-position: center;">
                <div class="absolute inset-0 bg-black opacity-35 group-hover:opacity-70 pointer-events-none transition-opacity duration-300"></div>
                <div class="relative z-10 flex items-start justify-between">
                    <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
                    <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
                </div>
                <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Los Angeles</div>
            </a>
            <a href="https://book.primaapp.com/ibiza" target="_blank"
               class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInRight"
               style="background-image: url('{{ asset('images/site/Ibiza.webp') }}'); background-size: cover; background-position: center;">
                <div class="absolute inset-0 bg-black opacity-35 group-hover:opacity-70 pointer-events-none transition-opacity duration-300"></div>
                <div class="relative z-10 flex items-start justify-between">
                    <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
                    <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
                </div>
                <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Ibiza</div>
            </a>
        </div>
    </div>
    <div class="text-center mt-6 wow animate__animated animate__fadeInUp">
        <button type="button" data-target="panelHeader"
                class="cta-btn cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
            PRIMA</button>
    </div>
</section>

<!-- WHY RESTAURANTS USE PRIMA -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
        <h2 class="text-4xl font-semibold mt-4 mb-6 wow animate__animated animate__zoomIn">
            Why Restaurants Work With PRIMA
        </h2>

        <div class="grid md:grid-cols-2 gap-6 text-left wow animate__animated animate__zoomIn">
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="users" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">High‑Intent Guests</h3>
                    <p class="text-black">Reach travelers and locals already in the city and actively looking to book a memorable experience tonight or this week.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="circle-dollar-sign" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Monetize Sold‑Out Demand</h3>
                    <p class="text-black">Offer premium access for peak tables. PRIMA shares 60% of the access fee with your restaurant.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="bar-chart-3" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Measure Every Channel</h3>
                    <p class="text-black">See performance by hotel, concierge, influencer, or promo. Compare covers, revenue, and net margin.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="network" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">One Platform For Promoters</h3>
                    <p class="text-black">Manage internal hosts, external promoters, and influencers with unified tracking and payouts.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-6 mb-3 wow animate__animated animate__fadeInUp">
            <button type="button" data-target="panelHeader"
                    class="cta-btn cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
                PRIMA</button>
        </div>
    </div>
</section>

<!-- HOW IT WORKS FOR RESTAURANTS -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
        <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
            <span class="relative text-center z-10">How It
                <span class="relative inline-block">
                    <span class="inline-block relative z-10">Works</span>
                    <span class="absolute left-0 right-0 -bottom-1 h-5 z-0 pointer-events-none"
                          style="background: url('{{ asset('images/site/scribble-underline.svg') }}') no-repeat center bottom; background-size: contain; width: 100%;"></span>
                </span>
            </span>
        </h2>

        <div class="grid md:grid-cols-3 gap-4 text-left wow animate__animated animate__zoomIn">
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
                        <i data-lucide="check-circle" class="w-[50px] h-[50px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Activate</h3>
                    <p class="text-black">Connect your reservation system or choose messaging fulfillment. Set table rules and pricing for premium access.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
                        <i data-lucide="mouse-pointer-click" class="w-[50px] h-[50px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Distribute</h3>
                    <p class="text-black">Publish inventory to hotels, concierges, influencers, and your own channels via the PRIMA interface.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
                        <i data-lucide="wallet" class="w-[50px] h-[50px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Settle</h3>
                    <p class="text-black">PRIMA attributes each seated cover and deposits payouts twice monthly with full reporting.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS WITH SOURCES -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl stripe-gradient-bg shadow-md p-4 text-center">
        <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
            <span class="relative text-center z-10">What The
                <span class="relative inline-block">
                    <span class="inline-block relative z-10">Data</span>
                    <span class="absolute left-0 right-0 -bottom-1 h-5 z-0 pointer-events-none"
                          style="background: url('{{ asset('images/site/scribble-underline.svg') }}') no-repeat center bottom; background-size: contain; width: 100%;"></span>
                </span>
                Says
            </span>
        </h2>

        <div class="grid md:grid-cols-3 gap-4 text-left wow animate__animated animate__zoomIn">
            <!-- First -->
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600 text-white text-1xl md:text-[20px] font-extrabold">
                        41%
                    </span>
                </div>
                <p class="text-black">
                    Fine dining sales from travelers and visitors in typical pre-COVID years, showing out-of-town guests are a major revenue.
                    <a class="text-indigo-600 underline text-xs" target="_blank" href="https://restaurant.org/education-and-resources/resource-library/tourism-related-spending-in-restaurants-fell-sharply-in-recent-months/">National Restaurant Association</a>
                </p>
            </div>

            <!-- Second -->
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-emerald-600 text-white text-1xl md:text-[20px] font-extrabold">
                        53%
                    </span>
                </div>
                <p class="text-black">
                    Share of U.S. hotel-guest spending that occurs off property, indicating strong demand for local dining.
                    <a class="text-indigo-600 underline text-xs" target="_blank" href="https://www.costar.com/article/1787677919/majority-of-hotel-guest-spending-comes-outside-of-room-rate">CoStar, 2023</a>
                </p>
            </div>

            <!-- Third -->
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-pink-600 text-white text-1xl md:text-[20px] font-extrabold">
                        50%
                    </span>
                </div>
                <p class="text-black">
                    Hotel guests likely to order delivery or dine at outside restaurants during a stay.
                    <a class="text-indigo-600 underline text-xs" target="_blank" href="https://cgastrategy.com/go-technology-hotels-and-consumers/">CGA Strategy, 2025</a>
                </p>
            </div>

            <!-- Fourth -->
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-yellow-500 text-white text-1xl md:text-[20px] font-extrabold">
                        44%
                    </span>
                </div>
                <p class="text-black">
                    Diners who get frustrated or stop trying when reservations are hard to make.
                    <a class="text-indigo-600 underline text-xs" target="_blank" href="https://pos.toasttab.com/blog/on-the-line/restaurant-reservations-and-wait-times-data">Toast Data Study</a>
                </p>
            </div>

            <!-- Fifth -->
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-red-600 text-white text-1xl md:text-[20px] font-extrabold">
                        65%
                    </span>
                </div>
                <p class="text-black">
                    Diners who go directly to the restaurant website to book, reinforcing the value of a clean, direct PRIMA interface.
                    <a class="text-indigo-600 underline text-xs" target="_blank" href="https://pos.toasttab.com/blog/on-the-line/restaurant-reservations-and-wait-times-data">Toast Data Study</a>
                </p>
            </div>

            <!-- Sixth -->
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
                <div class="flex flex-col h-full">
                    <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-blue-600 text-white text-1xl md:text-[20px] font-extrabold">
                        25%
                    </span>
                </div>
                <p class="text-black">
                    Food tourists spend about a quarter of their travel budget on food and beverages, signaling higher spend potential.
                    <a class="text-indigo-600 underline text-xs" target="_blank" href="https://www.ucf.edu/online/hospitality/news/food-tourism/">UCF citing WFTA</a>
                </p>
            </div>
        </div>

        <p class="text-xs text-slate-500 mt-4 wow animate__animated animate__fadeInUp">Sources are independent of PRIMA. Linked above.</p>
    </div>
</section>

<!-- INTEGRATIONS / FULFILLMENT -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
        <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
            <span class="relative text-center z-10">
                <span class="relative inline-block">
                    <span class="inline-block relative z-10">Integrations</span>
                    <span class="absolute left-0 right-0 -bottom-3 h-5 z-0 pointer-events-none"
                          style="background: url('{{ asset('images/site/scribble-underline.svg') }}') no-repeat center bottom; background-size: contain; width: 100%;"></span>
                </span>
                And Fulfillment
            </span>
        </h2>

        <div class="grid md:grid-cols-2 gap-4 text-left">
            <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="plug" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Works With Your Stack</h3>
                    <p class="text-black">PRIMA integrates with leading reservation and table‑management systems. Inventory and covers post to your backend automatically.</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="message-circle" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">No System? No Problem</h3>
                    <p class="text-black">Operate via SMS or WhatsApp with management confirmations, secure links, and full audit trail.</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="file-chart-column" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Channel Performance</h3>
                    <p class="text-black">Attribute every cover to the source. Compare hotels, concierges, influencers, and local marketing in one dashboard.</p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
                <div class="flex flex-col justify-center h-full">
                    <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
                        <i data-lucide="shield-check" class="w-[60px] h-[60px] text-white"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-[18px] md:text-[23px] font-semibold">Controls And Guardrails</h3>
                    <p class="text-black">Set spend floors, party sizes, release windows, and blackout dates to protect your average check and guest mix.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-6 mb-3 wow animate__animated animate__fadeInUp">
            <button type="button" data-target="panelHeader"
                    class="cta-btn cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
                PRIMA</button>
        </div>
    </div>
</section>

<!-- FREQUENTLY ASKED QUESTIONS SECTION -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4">
        <h2 class="text-4xl font-semibold text-center mt-4 mb-8 wow animate__animated animate__zoomIn">
            Frequently Asked Questions
        </h2>
        <div class="faq-list flex flex-col gap-4">
            <div class="faq-item border rounded-xl overflow-hidden wow animate__animated animate__fadeInUp">
                <button class="w-full text-left px-6 py-4 bg-white font-semibold text-lg flex justify-between items-center faq-toggle"
                        aria-expanded="true">
                    <span>How does PRIMA ensure we hit or exceed average cover?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                </button>
                <div class="faq-answer px-6 pb-4 bg-white text-slate-700 block">
                    You set rules on party size, spend floors, and release timing. PRIMA routes high‑intent demand to tables that fit your goals and measures realized check size.
                </div>
            </div>
            <div class="faq-item border rounded-xl overflow-hidden wow animate__animated animate__fadeInUp">
                <button class="w-full text-left px-6 py-4 bg-white font-semibold text-lg flex justify-between items-center faq-toggle"
                        aria-expanded="false">
                    <span>What about cancellations and no‑shows?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                </button>
                <div class="faq-answer bg-white px-6 pb-4 text-slate-700 hidden">
                    Automated confirmations, secure holds, and policy enforcement reduce risk. You control penalties by channel.
                </div>
            </div>
            <div class="faq-item border rounded-xl overflow-hidden wow animate__animated animate__fadeInUp">
                <button class="w-full text-left px-6 py-4 bg-white font-semibold text-lg flex justify-between items-center faq-toggle"
                        aria-expanded="false">
                    <span>How are fees handled on sold‑out access?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                </button>
                <div class="faq-answer bg-white px-6 pb-4 text-slate-700 hidden">
                    PRIMA charges an access fee for premium tables. 60% of that fee is paid to your restaurant in addition to the guest check.
                </div>
            </div>
            <div class="faq-item border rounded-xl overflow-hidden wow animate__animated animate__fadeInUp">
                <button class="w-full text-left px-6 py-4 bg-white font-semibold text-lg flex justify-between items-center faq-toggle"
                        aria-expanded="false">
                    <span>Can we manage our promoter network here?</span>
                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                </button>
                <div class="faq-answer bg-white px-6 pb-4 text-slate-700 hidden">
                    Yes. Track internal hosts and external promoters from one dashboard with unified links, attribution, and payouts.
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.faq-toggle').forEach(function (btn, idx) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.faq-toggle').forEach(function (b, i) {
                    b.setAttribute('aria-expanded', 'false');
                    b.parentElement.querySelector('.faq-answer').classList.add('hidden');
                });
                btn.setAttribute('aria-expanded', 'true');
                btn.parentElement.querySelector('.faq-answer').classList.remove('hidden');
            });
        });
        // Open first by default
        var firstBtn = document.querySelector('.faq-toggle');
        if (firstBtn) {
            firstBtn.setAttribute('aria-expanded', 'true');
            firstBtn.parentElement.querySelector('.faq-answer').classList.remove('hidden');
        }
    });
</script>
@endpush