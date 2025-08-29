<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PRIMA - The intelligence and profit layer for hospitality</title>
  <!-- Tailwind (no defer so styles apply immediately) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest" defer></script>
  <style>
    .collapsible{max-height:0; opacity:0; overflow:hidden; transition:max-height .35s ease, opacity .25s ease}
    .collapsible.open{max-height:2000px; opacity:1}
    body{background:white;}
    .splash { position:absolute; inset:0;
      background: radial-gradient(circle at 20% 30%, rgba(99,102,241,0.25), transparent 40%),
                  radial-gradient(circle at 80% 20%, rgba(139,92,246,0.25), transparent 40%),
                  radial-gradient(circle at 50% 80%, rgba(14,165,233,0.25), transparent 40%);
      z-index:0; }
  </style>
</head>
<body class="min-h-screen text-slate-900">
  <!-- HEADER -->
  <header class="sticky top-0 z-40 backdrop-blur bg-white/70 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
      <div class="font-bold text-2xl tracking-tight text-indigo-600">PRIMA</div>
      <div class="flex items-center gap-3">
        <a href="https://primavip.co/platform/login" target="_blank" class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600">Login</a>
        <button type="button" data-target="panelHeader" class="cta-btn px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Work With PRIMA</button>
      </div>
    </div>
    <!-- Header Lead Form -->
    <div id="panelHeader" class="collapsible">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 border-t border-slate-200 bg-white shadow-sm">
        <h4 class="text-lg font-semibold">Tell us a bit about you</h4>
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
          <input required name="fullName" placeholder="Full Name" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
          <input name="company" placeholder="Company / Property" class="col-span-2 px-3 py-2 rounded-xl border border-slate-300" />
          <input required type="email" name="email" placeholder="Email" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
          <input name="phone" placeholder="Phone" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
          <input name="city" placeholder="City" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
          <input name="preferredTime" placeholder="Preferred Contact Time" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />
          <textarea name="notes" rows="3" placeholder="Anything else we should know?" class="col-span-2 px-3 py-2 rounded-xl border border-slate-300"></textarea>
          <div class="col-span-2 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Submit</button>
            <button type="button" data-close="panelHeader" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600">Close</button>
          </div>
        </form>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <section id="hero" class="relative overflow-hidden pt-20 pb-16 bg-white">
    <div class="splash"></div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
      <h1 class="text-6xl md:text-7xl font-extrabold tracking-tight text-slate-900">The <span class="text-indigo-600">Platform</span> That <span class="text-indigo-600">Connects</span> Hospitality</h1>
      <p class="mt-6 text-2xl text-slate-700 max-w-3xl mx-auto">PRIMA connects <strong>Hotels</strong>, <strong>Restaurants</strong>, <strong>Concierges</strong>, <strong>Influencers</strong>, and <strong>Guests</strong> into one ecosystem where attribution is precise, reporting is unified, and experiences are unforgettable.</p>
    </div>
  </section>

  <!-- LIVE CITIES -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8 text-center">
      <p class="text-2xl font-semibold mb-6">PRIMA is LIVE in:</p>
      <div class="flex flex-col sm:flex-row gap-6 justify-center">
        <!-- Miami -->
        <a href="{{ config('app.booking_url') }}/miami" target="_blank" class="flex-1 group rounded-2xl overflow-hidden shadow-md hover:shadow-lg transition max-w-xs">
          <img src="https://images.unsplash.com/photo-1503602642458-232111445657?q=80&w=1200&auto=format&fit=crop" alt="Miami" class="w-full h-40 object-cover" />
          <div class="bg-white p-4 flex items-center justify-between">
            <span class="text-lg font-semibold text-slate-900">Miami</span>
            <span class="text-indigo-700 font-medium group-hover:underline">Book Now</span>
          </div>
        </a>
        <!-- Los Angeles -->
        <a href="{{ config('app.booking_url') }}/los-angeles" target="_blank" class="flex-1 group rounded-2xl overflow-hidden shadow-md hover:shadow-lg transition max-w-xs">
          <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1200&auto=format&fit=crop" alt="Los Angeles" class="w-full h-40 object-cover" />
        	<div class="bg-white p-4 flex items-center justify-between">
            <span class="text-lg font-semibold text-slate-900">Los Angeles</span>
            <span class="text-indigo-700 font-medium group-hover:underline">Book Now</span>
          </div>
        </a>
        <!-- Ibiza -->
        <a href="{{ config('app.booking_url') }}/ibiza" target="_blank" class="flex-1 group rounded-2xl overflow-hidden shadow-md hover:shadow-lg transition max-w-xs">
          <img src="https://images.unsplash.com/photo-1500375592092-40eb2168fd21?q=80&w=1200&auto=format&fit=crop" alt="Ibiza" class="w-full h-40 object-cover" />
          <div class="bg-white p-4 flex items-center justify-between">
            <span class="text-lg font-semibold text-slate-900">Ibiza</span>
            <span class="text-indigo-700 font-medium group-hover:underline">Book Now</span>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- ATTRIBUTION BLOCK -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8">
      <h2 class="text-3xl font-bold mb-6">Attribution, Not Assumptions</h2>
      <div class="grid md:grid-cols-2 gap-6">
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <i data-lucide="activity" class="w-16 h-16 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Source-Level Attribution</h3>
            <p class="text-slate-600">See exactly where every booking came from and who deserves credit. PRIMA eliminates guesswork and ensures every stakeholder receives recognition for the value they create.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <i data-lucide="layout-dashboard" class="w-16 h-16 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Unified Reporting</h3>
            <p class="text-slate-600">One dashboard for hotels, restaurants, and concierges to track value in real time. Data flows seamlessly across the ecosystem to provide clarity and eliminate silos.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <i data-lucide="calendar-x" class="w-16 h-16 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Eliminate No-Shows</h3>
            <p class="text-slate-600">67% of bookings on PRIMA are for today or tomorrow, showing higher intent and drastically reducing no-shows. Venues save time, protect their revenue, and ensure seats are filled.</p>
          </div>
        </div>
        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white transition hover:bg-violet-100">
          <i data-lucide="stars" class="w-16 h-16 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Enhanced Guest Experience</h3>
            <p class="text-slate-600">Make every interaction seamless and memorable for high-value guests. PRIMA ensures a premium experience by streamlining booking and guaranteeing access.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOTELS SECTION -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8">
      <h2 class="text-3xl font-bold mb-4">Hotels, Residential Communities and AirBNBs</h2>
      <p class="mb-4 text-slate-700">PRIMA allows hotels and properties to provide guests with access to exclusive restaurants, driving satisfaction and creating a premium amenity. Real-time attribution ensures hotels see value clearly.</p>
      <ul class="list-disc pl-6 text-slate-700 space-y-2">
        <li>Drive guest satisfaction with exclusive access</li>
        <li>Generate new revenue streams through partnerships</li>
        <li>Provide clear reporting and accountability</li>
      </ul>
      <button type="button" data-target="panelHeader" class="cta-btn mt-4 px-5 py-3 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Join PRIMA</button>
    </div>
  </section>

  <!-- RESTAURANTS SECTION -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8">
      <h2 class="text-3xl font-bold mb-4">Restaurants</h2>
      <p class="mb-4 text-slate-700">Restaurants use PRIMA to optimize table utilization and reduce no-shows while gaining access to high-value guests from hotels and concierge networks.</p>
      <ul class="list-disc pl-6 text-slate-700 space-y-2">
        <li>Fill tables with last-minute high-intent guests</li>
        <li>Track performance with transparent reporting</li>
        <li>Reduce no-shows with PRIMA's high-intent bookings</li>
      </ul>
      <button type="button" data-target="panelHeader" class="cta-btn mt-4 px-5 py-3 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Join PRIMA</button>
    </div>
  </section>

  <!-- CONCIERGES SECTION -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8">
      <h2 class="text-3xl font-bold mb-4">Hotel and Lifestyle Concierges</h2>
      <p class="mb-4 text-slate-700">Concierges benefit from transparent attribution and streamlined booking processes, ensuring they are recognized and rewarded for driving guest reservations.</p>
      <ul class="list-disc pl-6 text-slate-700 space-y-2">
        <li>Get full credit for every reservation placed</li>
        <li>Streamline workflows with real-time availability</li>
        <li>Enhance guest experiences through premium access</li>
      </ul>
      <button type="button" data-target="panelHeader" class="cta-btn mt-4 px-5 py-3 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Earn With PRIMA</button>
    </div>
  </section>

  <!-- INFLUENCERS SECTION -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-16">
    <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 shadow-md p-8">
      <h2 class="text-3xl font-bold mb-4">Food Influencers & Bloggers</h2>
      <p class="mb-4 text-slate-700">Influencers and bloggers can now prove their impact beyond likes and views. With PRIMA, every post that drives bookings is tracked and reported, giving creators a clear way to monetize influence.</p>
      <ul class="list-disc pl-6 text-slate-700 space-y-2">
        <li>Track bookings driven by your content</li>
        <li>Monetize your influence with real results</li>
        <li>Partner with top restaurants and hotels worldwide</li>
      </ul>
      <button type="button" data-target="panelHeader" class="cta-btn mt-4 px-5 py-3 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Earn With PRIMA</button>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-slate-100 border-t border-slate-200 py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-slate-600">
      <a href="https://instagram.com/bookwithprima" target="_blank" class="flex items-center gap-2 mb-2 sm:mb-0">
        <i data-lucide="instagram" class="w-5 h-5"></i>
        @bookwithprima
      </a>
      <p class="text-sm">© 2025 PRIMA. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Initialize Lucide icons safely
    function initIcons(){
      try{ if(window.lucide && typeof window.lucide.createIcons === 'function'){ window.lucide.createIcons(); } }
      catch(e){ console.warn('Lucide init failed', e); }
    }

    // Toggle helper for any element with data-target / data-close
    function wireCollapsibles(){
      document.querySelectorAll('[data-target]').forEach(btn=>{
        btn.addEventListener('click',()=>{
          const id = btn.getAttribute('data-target');
          const panel = document.getElementById(id);
          if(!panel) return;
          panel.classList.toggle('open');
          if(panel.classList.contains('open')){
            panel.scrollIntoView({behavior:'smooth', block:'nearest'});
          }
          initIcons();
        });
      });
      document.querySelectorAll('[data-close]').forEach(btn=>{
        btn.addEventListener('click',()=>{
          const id = btn.getAttribute('data-close');
          const panel = document.getElementById(id);
          if(panel){ panel.classList.remove('open'); }
        });
      });
    }

    // Boot
    window.addEventListener('DOMContentLoaded',()=>{
      wireCollapsibles();
      initIcons();
    });
  </script>
</body>
</html>
