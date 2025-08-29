@extends('site.layout')

@section('title', 'PRIMA - The intelligence and profit layer for hospitality')

@section('lead-form')
<!-- SYNC:PRESERVE:START:lead-form -->
<div id="panelHeader" class="collapsible">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 border-t border-slate-200 bg-white shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold">Talk to PRIMA</h3>
        <button type="button" class="close-btn text-slate-500 hover:text-slate-700" data-close="panelHeader"
          aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <livewire:site-contact-form />
    </div>
  </div>
<!-- SYNC:PRESERVE:END -->
@endsection

@section('content')
<div id="modalOverlay" class="modal-overlay"></div>
<section id="hero" class="relative py-10">
    <div class="splash"></div>
    <div class="max-w-5xl mx-auto px-4 md:px-6 relative z-10">
      <div class="flex flex-col justify-center items-center text-center wow animate__animated animate__zoomIn">
        <h1 class="text-[34px] md:text-[65px] leading-[1] font-black">Give Your <span class="text-indigo-600">Guests</span> The <span class="text-indigo-600">Amenity</span> They Truly Want
        </h1>
        <p class="mt-7 text-xl">PRIMA connects <strong>hotel and AirBNB</strong> guests to top restaurants in your city
          with instant booking, clear benefits, and shared revenue for your property.
        </p>
        <div class="grid md:grid-cols-3 gap-5 mt-6">
          <div class="flex items-center gap-4">
            <div class="flex flex-col justify-center h-full">
              <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600">
                <i data-lucide="qr-code" class="w-[60px] h-[60px] text-white"></i>
              </span>
            </div>
            <div class="text-left">
              <p>In‑room QR code access that opens a curated booking hub.</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex flex-col justify-center h-full">
              <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600">
                <i data-lucide="mail" class="w-[60px] h-[60px] text-white"></i>
              </span>
            </div>
            <div class="text-left">
              <p>Pre‑arrival notices and post check‑in texts or emails that drive usage.</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex flex-col justify-center h-full">
              <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600">
                <i data-lucide="users" class="w-[60px] h-[60px] text-white"></i>
              </span>
            </div>
            <div class="text-left">
              <p>Concierge tools with full transparency of bookings and guest benefits.</p>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="relative flex items-center justify-center mt-10 wow animate__animated animate__zoomIn">

      <!-- Heading above the curve -->
      <h2 class="relative z-10 bg-white px-4 text-3xl font-semibold text-center mb-6">
        <span class="relative text-center z-10">
          PRIMA is
          <span class="relative inline-block">
            <span class="inline-block relative z-10">LIVE</span>
            <span class="absolute left-0 right-0 bottom-0 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
          in
        </span>
      </h2>
    </div>

    <div class="rounded-2xl overflow-hidden shadow-md border border-slate-300">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-[1px]">
        <a href="{{ config('app.booking_url') }}/?region=miami" target="_blank" class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInLeft" style="background-image: url('{{ asset('images/site/miami.jpg') }}' ); background-size: cover; background-position: center center;">
          <div class="absolute inset-0 bg-black opacity-30 group-hover:opacity-50 pointer-events-none transition-opacity duration-300">
          </div>
          <div class="relative z-10 flex items-start justify-between">
            <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
            <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
          </div>
          <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Miami</div>
        </a>
        <a href="{{ config('app.booking_url') }}/?region=los-angeles" target="_blank" class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInUp" style="background-image: url('{{ asset('images/site/los-angeles.jpg') }}' ); background-size: cover; background-position: center center;">
          <div class="absolute inset-0 bg-black opacity-30 group-hover:opacity-50 pointer-events-none transition-opacity duration-300">
          </div>
          <div class="relative z-10 flex items-start justify-between">
            <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
            <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
          </div>
          <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Los Angeles</div>
        </a>
        <a href="{{ config('app.booking_url') }}/?region=ibiza" target="_blank" class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInRight" style="background-image: url('{{ asset('images/site/Ibiza.webp') }}' ); background-size: cover; background-position: center bottom;">
          <div class="absolute inset-0 bg-black opacity-30 group-hover:opacity-50 pointer-events-none transition-opacity duration-300">
          </div>
          <div class="relative z-10 flex items-start justify-between">
            <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
            <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
          </div>
          <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Ibiza</div>
        </a>
      </div>
    </div>
    <div class="text-center mt-6 mb-4 wow animate__animated animate__fadeInUp">
      <button type="button" data-target="panelHeader" class="px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Partner
        With
        PRIMA</button>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">Why
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Hotels and AirBNB</span>
            <span class="absolute left-0 right-0 -bottom-3 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
          Hosts Choose PRIMA
        </span>
      </h2>

      <div class="grid md:grid-cols-2 gap-4 text-left">
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="key" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Access Sold‑Out Venues</h3>
            <p class="text-black">Unlock tough reservations at in‑demand restaurants so your guests always have
              great options.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="credit-card" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Revenue Sharing</h3>
            <p class="text-black">Earn a share of booking revenue. Whether guests dine on property or off property,
              your property still earns.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="check-circle" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Full Transparency</h3>
            <p class="text-black">Track every booking and seated cover in real time. See guest benefits and
              commission status clearly.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="smile" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Delight Your Guests</h3>
            <p class="text-black">Deliver memorable dining experiences that lift satisfaction, reviews, and repeat
              bookings.</p>
          </div>
        </div>
      </div>
      <div class="text-center mt-6 mb-4 wow animate__animated animate__fadeInUp">
        <button type="button" data-target="panelHeader" class="px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Partner
          With
          PRIMA</button>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="wow animate__animated animate__fadeInUp">
      <img src="{{ asset('images/site/laptop.png') }}" alt="We built PRIMA for you" class="w-full max-w-3xl mx-auto mb-8">
    </div>
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        Simple Implementation
      </h2>

      <div class="grid md:grid-cols-3 gap-4 text-left wow animate__animated animate__zoomIn">
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
              <i data-lucide="qr-code" class="w-[50px] h-[50px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">In‑Room QR Touchpoints</h3>
            <p class="text-black">Place branded QR stands or tent cards in rooms, suites, and common areas. Guests
              scan and book in seconds.
            </p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
              <i data-lucide="send" class="w-[50px] h-[50px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Pre‑Arrival and On‑Property Messaging</h3>
            <p class="text-black">Send pre‑arrival emails, plus texts or emails after check‑in, with a direct link
              to your PRIMA hub.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
              <i data-lucide="headphones" class="w-[50px] h-[50px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Concierge Controls</h3>
            <p class="text-black">Concierge teams can book on behalf of guests with full crediting and visibility
              across departments.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl stripe-gradient-bg shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">Why It
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Matters</span>
            <span class="absolute left-0 right-0 -bottom-1 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
        </span>
      </h2>

      <div class="grid md:grid-cols-3 gap-4 text-left wow animate__animated animate__zoomIn">

        <!-- First -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600 text-white text-1xl md:text-[20px] font-extrabold">
              72%
            </span>
          </div>
          <p class="text-black">
            Travelers report dining out for most meals while on the road. Your guests want easy access to great
            restaurants.
            <a href="https://press.opentable.com/news-releases/news-release-details/opentable-reveals-travel-dining-trends" target="_blank" class="text-indigo-600 underline text-xs">[OpenTable, 2023]</a>
          </p>
        </div>

        <!-- Second -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-emerald-600 text-white text-1xl md:text-[20px] font-extrabold">
              69%
            </span>
          </div>
          <p class="text-black">
            Of U.S. hotel guest spending occurs outside room rate, including food and beverage in local economies.
            <a href="https://www.ahla.com/sites/default/files/2022-08/AHLA-Oxford-Economics-Hotel-Industry-Impact-Study.pdf" target="_blank" class="text-indigo-600 underline text-xs">[AHLA/Oxford Economics, 2022]</a>
          </p>
        </div>

        <!-- Third -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-pink-600 text-white text-1xl md:text-[20px] font-extrabold">
              44%
            </span>
          </div>
          <p class="text-black">
            Diners get frustrated when booking is difficult, which reduces intent to dine. PRIMA removes friction.
            <a href="https://pos.toasttab.com/blog/on-the-line/restaurant-diner-survey" target="_blank" class="text-indigo-600 underline text-xs">[Toast Diner Survey, 2023]</a>
          </p>
        </div>

      </div>

    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Transparent</span>
            <span class="absolute left-0 right-0 -bottom-3 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
          Reporting and Payments
        </span>
      </h2>

      <div class="grid md:grid-cols-2 gap-4 text-left">
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="monitor" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Property Reporting Panel</h3>
            <p class="text-black">View bookings, status changes, seated covers, and commissions in one dashboard.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="calendar" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Twice Monthly Payments</h3>
            <p class="text-black">Predictable payouts for all seated diners in the pay period with clear accruals.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="settings-2" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Easy Administration</h3>
            <p class="text-black">Modify or cancel reservations in a few clicks. Updates propagate to partners and
              guest notifications.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="shield" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Reliable Accuracy</h3>
            <p class="text-black">Every reservation and payout is logged with precision for audit‑ready
              transparency.</p>
          </div>
        </div>
      </div>
      <div class="text-center mt-6 mb-4 wow animate__animated animate__fadeInUp">
        <button type="button" data-target="panelHeader" class="px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">
          Partner With PRIMA
        </button>

      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8 text-center wow animate__animated animate__fadeInUp">
      <div class="flex flex-col items-center justify-center gap-4">
        <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600 mb-2">
          <i data-lucide="phone" class="w-[50px] h-[50px] text-white"></i>
        </span>
        <h2 class="text-4xl font-semibold mt-2 mb-4">Talk To Our Team</h2>
        <p class="text-lg text-black mb-6 max-w-2xl mx-auto">We are currently onboarding properties in Miami, New York,
          London, Los Angeles and Ibiza and are planning on launching in more markets in Q1 of 2026. To begin working
          with us, please schedule a call with our team, we’d love to talk to you.</p>
        <button type="button" data-target="panelHeader"  class="inline-block px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Schedule
          A Call</button>
      </div>
    </div>
  </section>
@endsection
