<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'PRIMA - The intelligence and profit layer for hospitality')</title>
    <!-- Tailwind CDN -->
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide icons -->
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <link href="{{ asset('css/site.css') }}" rel="stylesheet" />
    @stack('styles')
</head>

<body class="min-h-screen text-slate-900">
    <!-- Overlay for panelHeader -->
    <div id="modalOverlay" class="modal-overlay"></div>
    
    <!-- HEADER -->
    <header class="sticky top-0 z-30 backdrop-blur bg-white/70 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 wow animate__animated animate__fadeInUp">
            <div class="header-logo text-3xl tracking-tight text-indigo-600 font-Inter">
                <a href="{{ route('site.index') }}">PRIMA</a>
            </div>

            <!-- Desktop Navigation: center, hidden on screens <=991px -->
            <nav class="flex-1 flex justify-center hidden lg:flex">
                <ul class="nav_menu flex items-center gap-6">
                    <li><a href="{{ route('site.hotels') }}" class="nav_menu-item @if(request()->routeIs('site.hotels')) active @endif">Hotels</a></li>
                    <li><a href="{{ route('site.restaurants') }}" class="nav_menu-item @if(request()->routeIs('site.restaurants')) active @endif">Restaurants</a></li>
                    <li><a href="{{ route('site.concierges') }}" class="nav_menu-item @if(request()->routeIs('site.concierges')) active @endif">Concierges</a></li>
                    <li><a href="{{ route('site.influencers') }}" class="nav_menu-item @if(request()->routeIs('site.influencers')) active @endif">Influencers</a></li>
                </ul>
            </nav>

            <!-- Desktop right-side buttons, hidden on screens <=991px -->
            <div class="flex items-center gap-3 hidden lg:flex">
                <a href="https://primavip.co/platform/login" target="_blank"
                   class="px-4 py-2 rounded-full border border-slate-300 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600 flex items-center font-semibold text-base">
                    Login
                </a>
                <button type="button" data-target="panelHeader"
                        class="cta-btn px-2 py-2 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 transition-all duration-150 hover:opacity-90 text-white hover:bg-emerald-700">
                    Work With PRIMA
                </button>
            </div>

            <!-- Mobile language links, action buttons, and menu icon: only on screens <=991px -->
            <div class="flex items-center gap-2 lg:hidden language-links">
                <a href="https://primavip.co/platform/login" target="_blank"
                   class="px-3 py-1 rounded-xl border border-slate-300 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600 flex items-center text-sm font-semibold transition-colors">
                    Login
                </a>
                <button type="button" data-target="panelHeader"
                        class="cta-btn px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 text-white hover:bg-emerald-700 transition-all duration-150 hover:opacity-90 text-sm font-semibold">
                    Work With PRIMA
                </button>
                <button id="mobileMenuBtn"
                        class="flex items-center justify-center p-2 rounded-full border border-slate-300 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600"
                        aria-label="Open menu">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobileMenuOverlay" class="fixed inset-0 min-h-screen h-screen bg-black bg-opacity-40 z-[9999998] hidden lg:hidden"></div>

        <!-- Mobile Menu Drawer -->
        <div id="mobileMenu" class="fixed top-0 right-0 w-full max-w-xs min-h-screen h-screen bg-white shadow-2xl z-[9999999] transform translate-x-full transition-transform duration-300 ease-in-out lg:hidden flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <span class="text-2xl font-bold text-indigo-600">Menu</span>
                <button id="closeMobileMenu"
                        class="p-2 rounded-full border border-slate-300 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600"
                        aria-label="Close menu">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <nav class="px-6 py-4 flex-1 overflow-y-auto">
                <ul class="flex flex-col gap-4">
                    <li><a href="{{ route('site.hotels') }}" class="nav_menu-item @if(request()->routeIs('site.hotels')) active @endif">Hotels</a></li>
                    <li><a href="{{ route('site.restaurants') }}" class="nav_menu-item @if(request()->routeIs('site.restaurants')) active @endif">Restaurants</a></li>
                    <li><a href="{{ route('site.concierges') }}" class="nav_menu-item @if(request()->routeIs('site.concierges')) active @endif">Concierges</a></li>
                    <li><a href="{{ route('site.influencers') }}" class="nav_menu-item @if(request()->routeIs('site.influencers')) active @endif">Influencers</a></li>
                    <li><a href="https://primavip.co/platform/login" target="_blank" class="nav_menu-item">Login</a></li>
                    <li><a data-target="panelHeader" href="javascript:void(0)" class="nav_menu-item">Work With PRIMA</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Header Lead Form -->
    @yield('lead-form')

    <!-- Main Content -->
    @yield('content')

    <!-- FOOTER -->
    <footer class="bg-slate-100 border-t border-slate-200 py-6 mt-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-black">
            <a href="https://instagram.com/bookwithprima" target="_blank"
               class="flex items-center gap-2 mb-2 sm:mb-0 wow animate__animated animate__fadeInLeft">
                <i data-lucide="instagram" class="w-5 h-5"></i>
                @bookwithprima
            </a>
            <p class="text-sm wow animate__animated animate__fadeInRight">© 2025 PRIMA. All rights reserved.</p>
        </div>
    </footer>

    <!-- WOW.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>new WOW().init();</script>

    <script>
        // Initialize Lucide icons safely
        function initIcons() {
            try { if (window.lucide && typeof window.lucide.createIcons === 'function') { window.lucide.createIcons(); } }
            catch (e) { console.warn('Lucide init failed', e); }
        }

        // Toggle helper for any element with data-target / data-close
        function wireCollapsibles() {
            document.querySelectorAll('[data-target]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-target');
                    const panel = document.getElementById(id);
                    const overlay = document.getElementById('modalOverlay');
                    if (!panel) return;
                    panel.classList.add('open');
                    overlay.classList.add('open');
                    overlay.onclick = function () {
                        panel.classList.remove('open');
                        overlay.classList.remove('open');
                    };
                    initIcons();
                });
            });
            document.querySelectorAll('[data-close]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-close');
                    const panel = document.getElementById(id);
                    const overlay = document.getElementById('modalOverlay');
                    if (panel) {
                        panel.classList.remove('open');
                        // Wait for transition to finish before hiding overlay
                        setTimeout(() => {
                            if (overlay) { overlay.classList.remove('open'); }
                        }, 400);
                    } else {
                        if (overlay) { overlay.classList.remove('open'); }
                    }
                });
            });
        }

        // Mobile menu logic
        function wireMobileMenu() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const closeMobileMenu = document.getElementById('closeMobileMenu');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            if (mobileMenuBtn && mobileMenu && mobileMenuOverlay) {
                mobileMenuBtn.addEventListener('click', function () {
                    mobileMenu.classList.remove('translate-x-full');
                    mobileMenuOverlay.classList.remove('hidden');
                    initIcons();
                });
            }
            if (closeMobileMenu && mobileMenu && mobileMenuOverlay) {
                closeMobileMenu.addEventListener('click', function () {
                    mobileMenu.classList.add('translate-x-full');
                    mobileMenuOverlay.classList.add('hidden');
                });
            }
            // Close menu when clicking overlay
            if (mobileMenuOverlay && mobileMenu) {
                mobileMenuOverlay.addEventListener('click', function () {
                    mobileMenu.classList.add('translate-x-full');
                    mobileMenuOverlay.classList.add('hidden');
                });
            }
            // Prevent closing when clicking inside menu
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        }

        // Boot
        window.addEventListener('DOMContentLoaded', () => {
            wireCollapsibles();
            wireMobileMenu();
            initIcons();
        });
    </script>

    @stack('scripts')
</body>
</html>