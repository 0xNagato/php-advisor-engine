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
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-[3fr_2fr] gap-2 items-center h-full">
        <!-- Left Column: spans 2/3 on desktop -->
        <div class="flex flex-col justify-right justify-end wow animate__animated animate__fadeInLeft">
          <h1 class="text-[34px] md:text-[65px] leading-[1] font-black">The <span class="text-indigo-600">Platform</span> That <span class="text-indigo-600">Connects</span> Hospitality
          </h1>
          <div class="max-w-3xl mx-auto rounded-2xl items-start bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 mt-8 text-[18px] md:text-[20px] space-y-1 text-left">
            <p class="mb-4 text-lg font-semibold text-emerald-600">WITH PRIMA</p>
            <div>
              <label class="inline-flex items-start space-x-3 text-xl">
                <span class="flex items-center justify-center p-1 rounded-full border-2 border-indigo-600">
                  <i data-lucide="check" class="w-4 h-4 text-indigo-600"></i>
                </span>
                <span><strong>Guests</strong>
                  enjoy seamless, unforgettable experiences.</span></label>
            </div>
            <div><label class="inline-flex items-start space-x-3 text-xl"><span class="flex items-center justify-center p-1 rounded-full border-2 border-indigo-600"><i data-lucide="check" class="w-4 h-4 text-indigo-600"></i></span><span><strong>Restaurants</strong>
                  fill seats with
                  high-intent diners.</span></label></div>
            <div><label class="inline-flex items-start space-x-3 text-xl"><span class="flex items-center justify-center p-1 rounded-full border-2 border-indigo-600"><i data-lucide="check" class="w-4 h-4 text-indigo-600"></i></span><span><strong>Concierges</strong> get
                  credit for every
                  booking.</span></label></div>
            <div><label class="inline-flex items-start space-x-3 text-xl"><span class="flex items-center justify-center p-1 rounded-full border-2 border-indigo-600"><i data-lucide="check" class="w-4 h-4 text-indigo-600"></i></span><span><strong>Influencers</strong>
                  prove impact through
                  measurable bookings.</span></label>
            </div>
            <div><label class="inline-flex items-start space-x-3 text-xl"><span class="flex items-center justify-center p-1 rounded-full border-2 border-indigo-600"><i data-lucide="check" class="w-4 h-4 text-indigo-600"></i></span><span><strong>Hotels</strong>
                  generate ancillary revenue while improving service levels.</span></label></div>
            <div><label class="inline-flex items-start space-x-3 text-xl"><span class="flex items-center justify-center p-1 rounded-full border-2 border-indigo-600"><i data-lucide="check" class="w-4 h-4 text-indigo-600"></i></span><span><strong>Everybody</strong>
                  Wins<span style="position:relative;display:inline-block;"> <sup style="font-size:0.5em;position:absolute;top: 0.8em;right:-0.8em;">TM</sup>.</span></span></label>
            </div>
          </div>
        </div>

        <!-- Right Column: spans 1/3 on desktop -->
        <div class="flex justify-center md:justify-left mt-6 md:mt-0 wow animate__animated animate__fadeInRight">
          <img src="{{ asset('images/site/hero-placeholder.png') }}" alt="PRIMA platform preview" class="w-auto h-[700px] [transform:rotateY(20deg)_rotateX(-20deg)]">
        </div>
      </div>
      <div class="absolute w-[130px] md:w-[170px] z-[999999] bottom-[-90px] md:bottom-[-110px] right-[-5px] md:right-[10px] lg:right-[-60px] wow animate__animated animate__fadeInRight">
        <div>
          <img src="{{ asset('images/site/snapping-fingers.svg') }}" alt="Snapping fingers">
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
        <a href="https://book.primaapp.com/?region=miami" target="_blank" class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInLeft" style="background-image: url('{{ asset('images/site/miami.jpg') }}' ); background-size: cover; background-position: center center;">
          <div class="absolute inset-0 bg-black opacity-30 group-hover:opacity-50 pointer-events-none transition-opacity duration-300">
          </div>
          <div class="relative z-10 flex items-start justify-between">
            <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
            <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
          </div>
          <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Miami</div>
        </a>
        <a href="https://book.primaapp.com/?region=los-angeles" target="_blank" class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInUp" style="background-image: url('{{ asset('images/site/los-angeles.jpg') }}' ); background-size: cover; background-position: center center;">
          <div class="absolute inset-0 bg-black opacity-30 group-hover:opacity-50 pointer-events-none transition-opacity duration-300">
          </div>
          <div class="relative z-10 flex items-start justify-between">
            <span class="text-xs font-semibold tracking-widest text-white uppercase">Market</span>
            <button class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Book Now</button>
          </div>
          <div class="relative z-10 mt-1 text-xl sm:text-2xl font-extrabold text-white">Los Angeles</div>
        </a>
        <a href="https://book.primaapp.com/?region=ibiza" target="_blank" class="group relative p-4 transition overflow-hidden wow animate__animated animate__fadeInRight" style="background-image: url('{{ asset('images/site/Ibiza.webp') }}' ); background-size: cover; background-position:center 185px;">
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
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-6 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Attribution</span>
            <span class="absolute left-0 right-0 -bottom-1 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
          , Not Assumptions
        </span>
      </h2>

      <div class="grid md:grid-cols-2 gap-6 text-left wow animate__animated animate__zoomIn">
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col justify-start md:justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[120px] h-[120px] bg-indigo-600">
              <i data-lucide="activity" class="w-[65px] h-[65px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Source-Level Attribution</h3>
            <p class="text-black">See exactly where every booking came from and who deserves credit. PRIMA
              eliminates guesswork and ensures every stakeholder receives recognition for the value they create.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col justify-start md:justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[120px] h-[120px] bg-indigo-600">
              <i data-lucide="layout-dashboard" class="w-[65px] h-[65px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Unified Reporting</h3>
            <p class="text-black">One dashboard for hotels, restaurants, and concierges to track value in real time.
              Data flows seamlessly across the ecosystem to provide clarity and eliminate silos.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col justify-start md:justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[120px] h-[120px] bg-indigo-600">
              <i data-lucide="calendar-x" class="w-[65px] h-[65px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Eliminate No-Shows</h3>
            <p class="text-black">67% of bookings on PRIMA are for today or tomorrow, showing higher intent and
              drastically reducing no-shows. Venues save time, protect their revenue, and ensure seats are filled.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col justify-start md:justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[120px] h-[120px] bg-indigo-600">
              <i data-lucide="stars" class="w-[65px] h-[65px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Enhanced Guest Experience</h3>
            <p class="text-black">Make every interaction seamless and memorable for high-value guests. PRIMA ensures
              a premium experience by streamlining booking and guaranteeing access.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
    <div class="wow animate__animated animate__fadeInUp">
      <img src="{{ asset('images/site/we-built.png') }}" alt="We built PRIMA for you" class="w-full max-w-3xl mx-auto mb-8">
    </div>
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 md:p-8">

      <div class="grid md:grid-cols-12 gap-10 items-center justify-center">
        <div class="md:col-span-7 wow animate__animated animate__fadeInLeft">
          <h2 class="text-3xl md:text-4xl font-semibold mb-4">Hotels, Residential Communities and AirBNBs</h2>
          <p class="mb-6">PRIMA allows hotels and properties to provide guests with access
            to exclusive restaurants, driving satisfaction and creating a premium amenity. Real-time attribution ensures
            hotels see value clearly.</p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Drive guest satisfaction with exclusive access</li>
            <li>Generate new revenue streams through partnerships</li>
            <li>Provide clear reporting and accountability</li>
          </ul>
          <a href="{{ route('hotels') }}" class="inline-block mt-4 mr-2 px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Learn
            More</a>
          <a type="button" data-target="panelHeader" class="inline-block mt-4 cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
            PRIMA</a>

        </div>
        <div class="flex justify-center md:justify-center md:col-span-5 wow animate__animated animate__fadeInRight">
          <img src="{{ asset('images/site/hotels.jpg') }}" alt="Resort hotel pool at sunset" class="w-full md:max-w-xl rounded-2xl shadow-2xl ring-1 md:-mb-0 mb-[-175px] border-2 border-white ring-white/20 object-cover aspect-[4/3]">
        </div>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 md:mt-8 mt-[200px]">
    <div class="rounded-2xl overflow-hidden stripe-gradient-bg shadow-md p-4">
      <div class="grid lg:grid-cols-3 gap-2 md:gap-[1px] wow animate__animated animate__zoomIn">

        <!-- First Item -->
        <div class="flex h-full w-full flex-row items-center justify-start md:justify-center text-left pb-2 md:pb-0 border-b-2 md:border-b-0 md:border-r-2 border-slate-400 gap-4">
          <span class="flex items-center justify-center text-center rounded-full w-[70px] h-[70px] bg-indigo-600 text-white text-[20px] font-extrabold">
            <span class="flex items-center justify-center w-[70px] h-[70px]">72%</span>
          </span>
          <div>
            <p class="font-bold text-[20px]">72% of diners say it’s too hard to book reservations.</p>
          </div>
        </div>

        <!-- Second Item -->
        <div class="flex h-full w-full flex-row items-center justify-start md:justify-center text-left pl-0 md:pl-6 pb-2 md:pb-0 border-b-2 md:border-b-0 md:border-r-2 border-slate-400 gap-4">
          <span class="flex items-center justify-center text-center rounded-full w-[70px] h-[70px] bg-emerald-600 text-white text-[20px] font-extrabold">
            <span class="flex items-center justify-center w-[70px] h-[70px]">300%</span>
          </span>
          <div>
            <p class="font-bold text-[20px]">Hotel Guests Spend 3x as much as local diners.</p>
          </div>
        </div>

        <!-- Third Item -->
        <div class="flex h-full w-full flex-row items-center justify-left pl-0 md:pl-6 md:justify-center text-left gap-4">
          <span class="flex items-center justify-center text-center rounded-full w-[70px] h-[70px] bg-pink-600 text-white text-[20px] font-extrabold">
            <span class="flex items-center justify-center w-[70px] h-[70px]">&lt;2%</span>
          </span>
          <div>
            <p class="font-bold text-[20px]">PRIMA’s No-Show rate is under 2%</p>
          </div>
        </div>

      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 md:p-8">
      <div class="grid md:grid-cols-12 gap-10 items-center justify-center">
        <div class="md:col-span-7 wow animate__animated animate__fadeInLeft">
          <h2 class="text-3xl md:text-4xl font-semibold mb-4">Restaurants</h2>
          <p class="mb-6">Restaurants use PRIMA to optimize table utilization and reduce
            no-shows while gaining access to high-value guests from hotels and concierge networks.</p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Fill tables with last-minute high-intent guests</li>
            <li>Track performance with transparent reporting</li>
            <li>Reduce no-shows with PRIMA’s bookings</li>
          </ul>
          <a href="{{ route('restaurants') }}" class="inline-block mt-4 mr-2 px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Learn
            More</a>
          <a type="button" data-target="panelHeader" class="inline-block mt-4 cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
            PRIMA</a>
        </div>
        <div class="flex justify-center md:justify-start md:col-span-5 wow animate__animated animate__fadeInRight">
          <img src="{{ asset('images/site/restaurants.webp') }}" alt="Elegant restaurant interior" class="w-full md:max-w-xl rounded-2xl shadow-2xl ring-1 md:-mb-0 mb-[-175px] border-2 border-white ring-white/20 object-cover aspect-[4/3]">
        </div>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 md:mt-8 mt-[175px]">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 md:p-8">
      <div class="grid md:grid-cols-12 gap-10 items-center justify-center">
        <div class="md:col-span-7 wow animate__animated animate__fadeInLeft">
          <h2 class="text-3xl md:text-4xl font-semibold mb-4">Hotel and Lifestyle Concierges</h2>
          <p class="mb-6">Concierges benefit from transparent attribution and streamlined
            booking processes, ensuring they are recognized and rewarded for driving guest reservations.</p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Get full credit for every reservation placed</li>
            <li>Streamline workflows with real-time availability</li>
            <li>Enhance guest experiences through premium access</li>
          </ul>
          <a href="{{ route('concierges') }}" class="inline-block mt-4 mr-2 px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Learn
            More</a>
          <a type="button" data-target="panelHeader" class="inline-block mt-4 cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
            PRIMA</a>
        </div>
        <div class="flex justify-center md:justify-end md:col-span-5 wow animate__animated animate__fadeInRight">
          <img src="{{ asset('images/site/concierge.jpg') }}" alt="Concierge assisting guest at a hotel desk" class="w-full md:max-w-xl rounded-2xl shadow-2xl ring-1 md:-mb-0 mb-[-175px] border-2 border-white ring-white/20 object-cover aspect-[4/3]">
        </div>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-16 md:mt-8 mt-[175px]">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 md:p-8">
      <div class="grid md:grid-cols-12 gap-10 items-center justify-center">
        <div class="md:col-span-7 wow animate__animated animate__fadeInLeft">
          <h2 class="text-3xl md:text-4xl font-semibold mb-4">Food Influencers &amp; Bloggers</h2>
          <p class="mb-6">Influencers and bloggers can now prove their impact beyond likes
            and views. With PRIMA, every post that drives bookings is tracked and reported, giving creators a clear way
            to monetize influence.</p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Track bookings driven by your content</li>
            <li>Monetize your influence with real results</li>
            <li>Partner with top restaurants and hotels worldwide</li>
          </ul>
          <a href="{{ route('influencers') }}" class="inline-block mt-4 mr-2 px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Learn
            More</a>
          <a type="button" data-target="panelHeader" class="inline-block mt-4 cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
            PRIMA</a>
        </div>
        <div class="flex justify-center md:justify-start md:col-span-5 wow animate__animated animate__fadeInRight">
          <img src="{{ asset('images/site/influencers.webp') }}" alt="Food influencer capturing a restaurant dish" class="w-full md:max-w-xl rounded-2xl shadow-2xl ring-1 ring-white/20 object-cover border-2 border-white aspect-[4/3]">
        </div>
      </div>
    </div>
  </section>

<!-- SYNC:PRESERVE:START -->
    {{-- Custom Livewire components and modifications --}}
    {{-- Add your Livewire components here: --}}
    {{-- <livewire:booking-form /> --}}
    {{-- <livewire:contact-form /> --}}
<!-- SYNC:PRESERVE:END -->
@endsection