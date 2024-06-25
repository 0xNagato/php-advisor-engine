@php
    $testimonials = [
        [
            'quote' => "We've been waiting for a solution like PRIMA for a while to help book coveted reservations for our community. I'm very excited to begin using the platform.",
            'author' => 'Jorge Bargioni (Murano Portofino)'
        ],
        [
            'quote' => "PRIMA will make it much easier for us to book reservations for our residents. We consistently receive many requests that we cannot handle because reservations at top restaurants around the city are impossible to book. We're excited to partner with PRIMA to make this process much easier.",
            'author' => 'Edgar Gonzalez (Continuum On South Beach)'
        ],
        [
            'quote' => "PRIMA comes at the right time to help us deal with the onslaught of bots, fake reservations and cancellations.",
            'author' => 'Adam Borden (Groot Hospitality Group)'
        ],
        [
            'quote' => "Our concierge team is excited to use PRIMA to help book reservations for our guests as it is often very difficult to do so during the busy time of the season. We're excited to partner with PRIMA to also help fill our dining room.",
            'author' => 'Iria Urgell (Aguamadera Hotel)'
        ],
    ];
@endphp
<section {{ $attributes->class(['pt-[31px] px-[0] pb-[41px] md:pt-[75px] md:pb-[68px]']) }}>
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
            @foreach ($testimonials as $testimonial)
                <x-web.testimonial-item :testimonial="$testimonial" />
            @endforeach
        </div>
    </div>
</section>
