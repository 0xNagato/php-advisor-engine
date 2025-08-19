<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PRIMA for Concierges - The intelligence and profit layer for hospitality</title>
  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest" defer></script>
  <style>
    .collapsible { display: none; }
    .collapsible.open { display: flex; }
    body { background: white; }
    .splash { position: absolute; inset: 0;
      background: radial-gradient(circle at 20% 30%, rgba(99,102,241,0.25), transparent 40%),
                  radial-gradient(circle at 80% 20%, rgba(139,92,246,0.25), transparent 40%),
                  radial-gradient(circle at 50% 80%, rgba(14,165,233,0.25), transparent 40%);
      z-index: 0; }
  </style>
</head>
<body class="min-h-screen text-slate-900">
  <!-- HEADER -->
  <header class="sticky top-0 z-40 border-b backdrop-blur bg-white/70 border-slate-200">
    <div class="flex justify-between items-center px-4 mx-auto max-w-7xl h-16 sm:px-6 lg:px-8">
      <div class="text-2xl font-bold tracking-tight text-indigo-600">PRIMA</div>
      <div class="flex gap-3 items-center">
        <a href="https://primavip.co/platform/login" target="_blank" class="px-4 py-2 bg-white rounded-xl border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600">Login</a>
        <button type="button" data-target="panelHeader" class="px-4 py-2 text-white bg-emerald-600 rounded-xl cta-btn hover:bg-emerald-700">Work With PRIMA</button>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <section id="hero" class="overflow-hidden relative pt-20 pb-16 bg-white">
    <div class="splash"></div>
    <div class="relative z-10 px-4 mx-auto max-w-6xl text-center sm:px-6 lg:px-8">
      <h1 class="text-6xl font-extrabold tracking-tight md:text-7xl text-slate-900">Unlock <span class="text-indigo-600">Sold-Out</span> Tables</h1>
      <p class="mx-auto mt-6 max-w-3xl text-2xl text-slate-700">PRIMA empowers <strong>Hotel and Lifestyle Concierges</strong> with guaranteed access to exclusive reservations, transparent reporting, and shared revenue for every guest booking.</p>
    </div>
  </section>

  <!-- CLEFS D'OR SPONSOR -->
  <section class="px-4 mx-auto mt-8 max-w-4xl text-center sm:px-6 lg:px-8">
    <div class="p-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow-md">
      <p class="mb-4 text-xl font-semibold">Proud Sponsor of</p>
      <img src="https://upload.wikimedia.org/wikipedia/en/thumb/e/e8/Les_Clefs_d%27Or_logo.png/240px-Les_Clefs_d%27Or_logo.png" alt="Les Clefs d'Or" class="mx-auto mb-4 h-20"/>
      <p class="text-slate-700">PRIMA is honored to support <em>Les Clefs d'Or</em>, the world's most prestigious association of professional concierges, as an official sponsor.</p>
    </div>
  </section>

  <!-- VALUE FOR CONCIERGES -->
  <section class="px-4 mx-auto mt-12 max-w-6xl sm:px-6 lg:px-8">
    <div class="p-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow-md">
      <h2 class="mb-6 text-3xl font-bold">Why Concierges Choose PRIMA</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="key" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Access Sold-Out Venues</h3>
            <p class="text-slate-600">Unlock impossible-to-get reservations at the world's most in-demand restaurants, ensuring your guests always receive VIP treatment.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="credit-card" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Revenue Sharing</h3>
            <p class="text-slate-600">PRIMA generates revenue from every booking and shares that revenue directly with concierges, creating new income streams for your role.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="check-circle" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Get Full Credit</h3>
            <p class="text-slate-600">Every booking you make is tracked in real time, so you are recognized and rewarded for the value you bring to your property and partners.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="smile" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-semibold">Delight Guests</h3>
            <p class="text-slate-600">Enhance your guests' stay with unforgettable dining experiences that elevate satisfaction and loyalty.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ADVANTAGES SECTION -->
  <section class="px-4 mx-auto mt-12 max-w-6xl sm:px-6 lg:px-8">
    <div class="p-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow-md">
      <h3 class="mb-4 text-2xl font-bold">Advantages of Partnering with PRIMA</h3>
      <div class="grid gap-6 md:grid-cols-2">
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="layers" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-bold">Streamlined Workflow</h3>
            <p class="text-slate-600">Booking through the PRIMA Hub saves time and ensures accuracy in every reservation.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="bar-chart" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-bold">Real-Time Analytics</h3>
            <p class="text-slate-600">Monitor guest activity, seated reservations, and performance metrics as they happen.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="bell" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-bold">Automated Notifications</h3>
            <p class="text-slate-600">Receive confirmations and updates instantly for both concierge and guest.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="users" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-bold">Referral Benefits</h3>
            <p class="text-slate-600">Leverage PRIMA's referral program to extend your influence across properties.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="globe" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-bold">Trusted Global Network</h3>
            <p class="text-slate-600">Connect with top restaurants and hotels around the world through PRIMA's ecosystem.</p>
          </div>
        </div>
        <div class="flex gap-4 items-start p-4 bg-white rounded-xl border transition border-slate-200 hover:bg-violet-100">
          <i data-lucide="headphones" class="w-12 h-12 text-indigo-600"></i>
          <div>
            <h3 class="font-bold">Dedicated Support</h3>
            <p class="text-slate-600">Benefit from concierge-focused support to resolve issues quickly and maximize your success with PRIMA.</p>
          </div>
        </div>
      </div>
      <div class="mt-8 text-center">
        <button type="button" data-target="panelHeader" class="px-6 py-3 text-lg text-white bg-emerald-600 rounded-xl cta-btn hover:bg-emerald-700">Partner With PRIMA</button>
      </div>
    </div>
  </section>

  <!-- REPORTING PANEL SECTION -->
  <section class="px-4 mx-auto mt-12 max-w-6xl sm:px-6 lg:px-8">
    <div class="p-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow-md">
      <h3 class="mb-4 text-2xl font-bold">Transparent Reporting & Payments</h3>
      <p class="mb-6 text-slate-700">Concierges gain access to PRIMA's dedicated reporting panel, giving full visibility into every guest booking, status, and payout. Payments are issued twice monthly for all diners seated within each pay period, ensuring predictable and reliable income.</p>
      <div class="p-6 text-center bg-white rounded-xl border shadow-md border-slate-200">
        <img src="https://via.placeholder.com/800x400?text=PRIMA+Reporting+Panel+Preview" alt="PRIMA Reporting Panel" class="mx-auto rounded-lg shadow"/>
      </div>
    </div>
  </section>

  <!-- MARKETS SECTION -->
  <section class="px-4 mx-auto mt-12 max-w-6xl sm:px-6 lg:px-8">
    <div class="p-8 text-center bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow-md">
      <div class="px-4 mx-auto max-w-6xl text-center sm:px-6 lg:px-8">
        <h3 class="mb-8 text-3xl font-bold">PRIMA is Live In</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div class="overflow-hidden bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow transition hover:shadow-lg">
            <img src="https://via.placeholder.com/400x250?text=Miami" alt="Miami" class="object-cover w-full h-40"/>
            <div class="p-4 font-semibold">Miami</div>
          </div>
          <div class="overflow-hidden bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow transition hover:shadow-lg">
            <img src="https://via.placeholder.com/400x250?text=Los+Angeles" alt="Los Angeles" class="object-cover w-full h-40"/>
            <div class="p-4 font-semibold">Los Angeles</div>
          </div>
          <div class="overflow-hidden bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl shadow transition hover:shadow-lg">
            <img src="https://via.placeholder.com/400x250?text=Ibiza" alt="Ibiza" class="object-cover w-full h-40"/>
            <div class="p-4 font-semibold">Ibiza</div>
          </div>
        </div>
        <div class="mt-8 text-center">
          <button type="button" data-target="panelHeader" class="px-6 py-3 text-lg text-white bg-emerald-600 rounded-xl cta-btn hover:bg-emerald-700">Partner With PRIMA</button>
        </div>
      </div>
    </div>
  </section>

  <!-- LEAD FORM PANEL -->
  <div id="panelHeader" class="fixed inset-0 z-50 justify-center items-center collapsible bg-black/50">
    <div class="relative p-8 w-full max-w-lg bg-white rounded-2xl shadow-xl">
      <button data-close="panelHeader" class="absolute top-4 right-4 text-slate-500 hover:text-slate-700">✕</button>
      <h3 class="mb-4 text-2xl font-bold">Concierge Partnership Form</h3>
      <form class="space-y-4" onsubmit="event.preventDefault(); alert('Partnership request submitted!');">
        <div>
          <label class="block mb-1 text-sm font-medium">Full Name</label>
          <input type="text" class="px-3 py-2 w-full rounded-lg border border-slate-300" required />
        </div>
        <div>
          <label class="block mb-1 text-sm font-medium">Email</label>
          <input type="email" class="px-3 py-2 w-full rounded-lg border border-slate-300" required />
        </div>
        <div>
          <label class="block mb-1 text-sm font-medium">Hotel / Property</label>
          <input type="text" class="px-3 py-2 w-full rounded-lg border border-slate-300" required />
        </div>
        <div>
          <label class="block mb-1 text-sm font-medium">Message</label>
          <textarea class="px-3 py-2 w-full rounded-lg border border-slate-300" rows="4"></textarea>
        </div>
        <button type="submit" class="py-2 w-full text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Submit</button>
      </form>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="py-6 mt-12 border-t bg-slate-100 border-slate-200">
    <div class="flex flex-col justify-between items-center px-4 mx-auto max-w-6xl sm:px-6 lg:px-8 sm:flex-row text-slate-600">
      <a href="https://instagram.com/bookwithprima" target="_blank" class="flex gap-2 items-center mb-2 sm:mb-0">
        <i data-lucide="instagram" class="w-5 h-5"></i>
        @bookwithprima
      </a>
      <p class="text-sm">© 2025 PRIMA. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Initialize Lucide icons safely
    function initIcons() {
      try {
        if (window.lucide && typeof window.lucide.createIcons === "function") {
          window.lucide.createIcons();
        }
      } catch (e) {
        console.warn('Lucide init failed', e);
      }
    }

    // Toggle helper for any element with data-target / data-close
    function wireCollapsibles() {
      document.querySelectorAll('[data-target]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-target');
          const panel = document.getElementById(id);
          if (!panel) return;
          panel.classList.toggle('open');
          if (panel.classList.contains('open')) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
          initIcons();
        });
      });

      document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-close');
          const panel = document.getElementById(id);
          if (panel) {
            panel.classList.remove('open');
          }
        });
      });
    }

    // Boot the application
    window.addEventListener('DOMContentLoaded', () => {
      wireCollapsibles();
      initIcons();
    });
  </script>
</body>
</html>
