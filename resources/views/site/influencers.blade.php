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
        <h1 class="text-[34px] md:text-[65px] leading-[1] font-black">Turn <span class="text-indigo-600">Influence</span> Into <span class="text-indigo-600">Revenue</span></h1>
        <p class="mt-7 text-xl">With PRIMA, influencers and food bloggers get paid directly for the bookings they drive
          to restaurants and venues. No invoicing. No collections. Predictable payouts.
        </p>
        <div class="grid md:grid-cols-3 gap-5 mt-6">
          <div class="flex items-center gap-4">
            <div class="flex flex-col justify-center h-full">
              <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600">
                <i data-lucide="link" class="w-[60px] h-[60px] text-white"></i>
              </span>
            </div>
            <div class="text-left">
              <p>Share direct booking links for any venue on PRIMA.</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex flex-col justify-center h-full">
              <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600">
                <i data-lucide="smartphone" class="w-[60px] h-[60px] text-white"></i>
              </span>
            </div>
            <div class="text-left">
              <p>Place PRIMA in Linktree, bios, stories, email, or SMS.</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="flex flex-col justify-center h-full">
              <span class="flex items-center justify-center rounded-full w-[90px] h-[90px] bg-indigo-600">
                <i data-lucide="dollar-sign" class="w-[60px] h-[60px] text-white"></i>
              </span>
            </div>
            <div class="text-left">
              <p>Earn payouts twice monthly for seated bookings.</p>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">Why
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Influencers</span>
            <span class="absolute left-0 right-0 -bottom-1 h-4 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
          Work With PRIMA
        </span>
      </h2>

      <div class="grid md:grid-cols-2 gap-4 text-left">
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="megaphone" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Promote Any Venue</h3>
            <p class="text-black">Select from the PRIMA Marketplace and link directly to restaurants and nightlife
              venues your audience wants.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="credit-card" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Automated Payments</h3>
            <p class="text-black">No invoices or manual collection. PRIMA pays you twice monthly for completed
              bookings.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="mouse-pointer-click" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">One Dashboard</h3>
            <p class="text-black">See clicks, bookings, covers, and revenue in real time.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="send" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Flexible Sharing</h3>
            <p class="text-black">Send VIP links by text or email, or embed PRIMA in your bio. Any booking credits
              to your account.</p>
          </div>
        </div>
      </div>
      <div class="text-center mt-6 wow animate__animated animate__fadeInUp">
        <button type="button" data-target="panelHeader" class="cta-btn cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
          PRIMA</button>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">How It
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Works</span>
            <span class="absolute left-0 right-0 -bottom-1 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
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
            <h3 class="text-[18px] md:text-[23px] font-semibold">Pick Venues</h3>
            <p class="text-black">Choose any restaurant or group from the PRIMA Marketplace. Get unique tracking
              links.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
              <i data-lucide="share-2" class="w-[50px] h-[50px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Share</h3>
            <p class="text-black">Drop links in bios, Linktree, stories, posts, newsletters, and SMS. Use VIP links
              for 1:1 shares.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600">
              <i data-lucide="wallet" class="w-[50px] h-[50px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Get Paid</h3>
            <p class="text-black">Every seated booking is attributed to you. Payouts arrive twice monthly.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="wow animate__animated animate__fadeInUp">
      <img src="{{ asset('images/site/we-built.png') }}" alt="We built PRIMA for you" class="w-full max-w-3xl mx-auto mb-8">
    </div>
    <div class="rounded-2xl stripe-gradient-bg shadow-md p-4 text-center">
      <h2 class="text-4xl font-semibold mt-4 mb-8 wow animate__animated animate__zoomIn">
        <span class="relative text-center z-10">Why It
          <span class="relative inline-block">
            <span class="inline-block relative z-10">Converts</span>
            <span class="absolute left-0 right-0 -bottom-1 h-5 z-0 pointer-events-none" style="background: url('{{ asset('images/site/scribble-underline.svg') }}' ) no-repeat center bottom; background-size: contain; width: 100%;"></span>
          </span>
        </span>
      </h2>

      <div class="grid md:grid-cols-3 gap-4 text-left wow animate__animated animate__zoomIn">

        <!-- First -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-indigo-600 text-white text-1xl md:text-[20px] font-extrabold">
              $5.78
            </span>
          </div>
          <p class="text-black">
            Average return for every $1 spent on influencer marketing.
            <a href="https://www.storyclash.com/blog/en/influencer-marketing-roi/" target="_blank" class="text-indigo-600 underline text-xs">[Storyclash, 2024]</a>
          </p>
        </div>

        <!-- Second -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-emerald-600 text-white text-1xl md:text-[20px] font-extrabold">
              ~50%
            </span>
          </div>
          <p class="text-black">
            Consumers make a purchase at least monthly due to influencers.
            <a href="https://investors.sproutsocial.com/news/news-details/2024/New-Research-Reveals-Influencers-Significantly-Drive-Purchasing-Decisions/default.aspx" target="_blank" class="text-indigo-600 underline text-xs">[Sprout Social, 2024]</a>
          </p>
        </div>

        <!-- Third -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-pink-600 text-white text-1xl md:text-[20px] font-extrabold">
              $100B+
            </span>
          </div>
          <p class="text-black">
            US social commerce sales projected to pass $100B in 2026.
            <a href="https://www.emarketer.com/chart/269287/us-social-commerce-sales-will-surpass-100-billion-2026-billions-us-social-commerce-sales-change-of-total-retail-ecommerce-sales-2022-2028" target="_blank" class="text-indigo-600 underline text-xs">[eMarketer, 2024]</a>
          </p>
        </div>

        <!-- Fourth -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-amber-600 text-white text-1xl md:text-[20px] font-extrabold">
              20%
            </span>
          </div>
          <p class="text-black">
            Share of US Cyber Monday revenue driven by influencers and affiliates.
            <a href="https://www.businessinsider.com/influencers-social-other-affiliate-marketers-drove-us-cyber-monday-revenue-2024-12" target="_blank" class="text-indigo-600 underline text-xs">[Business Insider, 2024]</a>
          </p>
        </div>

        <!-- Fifth -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-red-600 text-white text-1xl md:text-[20px] font-extrabold">
              31M
            </span>
          </div>
          <p class="text-black">
            Instagram users using link-in-bio tools, led by Linktree.
            <a href="https://influencers.club/blog/state-of-the-link-in-bio-market/" target="_blank" class="text-indigo-600 underline text-xs">[Influencers Club, 2024]</a>
          </p>
        </div>

        <!-- Sixth -->
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <div class="flex flex-col h-full">
            <span class="flex items-center justify-center rounded-full w-[80px] h-[80px] bg-blue-600 text-white text-1xl md:text-[20px] font-extrabold">
              5 Links
            </span>
          </div>
          <p class="text-black">
            Threads now supports multiple bio links to drive traffic.
            <a href="https://www.theverge.com/news/667588/threads-multiple-profile-link-sharing-update" target="_blank" class="text-indigo-600 underline text-xs">[The Verge, 2025]</a>
          </p>
        </div>

      </div>

      <p class="text-xs text-slate-500 mt-4 wow animate__animated animate__fadeInUp">Sources are independent of PRIMA.
        Linked above.</p>
    </div>
  </section>
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="wow animate__animated animate__fadeInUp">
      <img src="{{ asset('images/site/laptop.png') }}" alt="We built PRIMA for you" class="w-full max-w-3xl mx-auto mb-8">
    </div>
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
              <i data-lucide="bar-chart-3" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Real‑Time Analytics</h3>
            <p class="text-black">Track clicks, bookings, and covers without spreadsheets.
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
            <p class="text-black">Predictable payouts for all seated diners in the pay period.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInLeft">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="check-circle" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Accurate Attribution</h3>
            <p class="text-black">Every reservation is logged with precision and audit‑ready detail.</p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100 wow animate__animated animate__fadeInRight">
          <div class="flex flex-col justify-center h-full">
            <span class="flex items-center justify-center rounded-full w-[100px] h-[100px] bg-indigo-600">
              <i data-lucide="smile" class="w-[60px] h-[60px] text-white"></i>
            </span>
          </div>
          <div>
            <h3 class="text-[18px] md:text-[23px] font-semibold">Simple Setup</h3>
            <p class="text-black">Start in minutes. Use VIP links for 1:1 shares. Add PRIMA to your bios.</p>
          </div>
        </div>
      </div>
      <div class="text-center mt-6 wow animate__animated animate__fadeInUp">
        <button type="button" data-target="panelHeader" class="cta-btn cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">Join
          PRIMA</button>
      </div>
    </div>
  </section>
@endsection
