<header class="header">
    <div class="header_container">
        <div class="header_logo">
            <a href="{{ route('home') }}">
                {{-- <img src="{{ asset('images/logo-768x184.png') }}" alt="prima-logo" class="header_logo-img"> --}}
                <x-filament-panels::logo />
            </a>
        </div>
        <div class="order-last nav xl:order-2">
            <div class="nav_wrap">
                <div class="flex items-center gap-4">
                    @auth()
                        <a href="{{ config('app.primary_domain') }}{{ config('app.platform_url') }}"
                            class="text-sm font-semibold text-primary hover:underline md:hidden">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ config('app.primary_domain') }}{{ config('app.platform_url') }}"
                            class="text-sm font-semibold text-primary hover:underline md:hidden">
                            Login
                        </a>
                    @endauth
                    <button class="nav_button">
                        <img src="{{ asset('images/marketing/menu_icon--light.svg') }}" alt="menu_icon"
                            class="nav_button-icon">
                    </button>
                </div>
                <ul class="nav_menu">
                    <li><a href="{{ route('home') }}"
                            class="nav_menu-item {{ request()->routeIs('home') ? 'nav_menu-item--active' : '' }}">Home</a>
                    </li>
                    <li><a href="{{ route('concierges') }}"
                            class="nav_menu-item {{ request()->routeIs('concierges') ? 'nav_menu-item--active' : '' }}">For
                            Concierges</a></li>
                    <li><a href="{{ route('restaurants') }}"
                            class="nav_menu-item {{ request()->routeIs('restaurants') ? 'nav_menu-item--active' : '' }}">For
                            Restaurants</a></li>
                    <li><a href="{{ route('consumers') }}"
                            class="nav_menu-item {{ request()->routeIs('consumers') ? 'nav_menu-item--active' : '' }}">For
                            Consumers</a></li>
                    <li><a href="{{ route('about-us') }}"
                            class="nav_menu-item {{ request()->routeIs('about-us') ? 'nav_menu-item--active' : '' }}">About
                            Us</a></li>
                    <li>
                        <div class="flex flex-col gap-2 md:hidden">
                            <a href="#" class="nav_menu-item"
                                @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                                Talk to PRIMA
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="hidden gap-4 xl:order-last md:flex">
            <a href="#" class="flex-auto py-2 text-white btn bg-primary"
                @click.prevent="$dispatch('prima-open-modal', { id: 'prima-contact' })">
                Talk to PRIMA
            </a>
            @auth()
                <a href="{{ config('app.primary_domain') }}{{ config('app.platform_url') }}"
                    class="flex-auto py-2 text-white btn bg-primary">
                    Dashboard
                </a>
            @else
                <a href="{{ config('app.primary_domain') }}{{ config('app.platform_url') }}"
                    class="flex-auto py-2 text-white btn bg-primary">
                    Login
                </a>
            @endauth
        </div>
    </div>
</header>
