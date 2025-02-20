<footer class="footer fade-in">
    <div class="container">
        <div class="footer_text">
            <div class="footer_text-logo">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('images/prima-logo--color.png') }}" alt="prima-logo"
                         class="footer_text-logo_img">
                </a>
                <p class="footer_text-description">
                    Unlock Prime Dining Reservations at the World’s Most Sought-After Restaurants
                </p>
            </div>
            <div class="footer_text-links">
                <div class="footer_text-links_cols">
                    <div class="footer_text-links_cols-item">
                        <p class="footer_headline">Home</p>
                        <a href="{{ route('about-us') }}" class="footer_text-links-item">About</a>
                        {{--                        <a href="contact-us.html" class="text-tiny text-neutral hover:underline">Contact</a>--}}
                    </div>
                    <div class="footer_text-links_cols-item sm:max-w-[160px]">
                        <p class="footer_headline">Available For</p>
                        <a href="{{ route('restaurants') }}" class="footer_text-links-item">Restaurant</a>
                        <a href="{{ route('concierges') }}" class="footer_text-links-item">Concierge</a>
                        <a href="{{ route('consumers') }}" class="footer_text-links-item">Consumers</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer_copyright">
            <p>
                © {{ now()->year }} PRIMA VIP. All rights reserved.
            </p>
        </div>
    </div>
</footer>


