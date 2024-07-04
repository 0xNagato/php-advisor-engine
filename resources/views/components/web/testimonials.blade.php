@php
    $testimonials = [
        [
            'quote' => "We've been waiting for a solution like PRIMA for a while to help book coveted reservations for our community. I'm very excited to begin using the platform.",
            'author' => 'Jorge Bargioni',
            'venue' => 'Murano Portofino'
        ],
        [
            'quote' => "PRIMA will make it much easier for us to book reservations for our residents. We consistently receive many requests that we cannot handle because reservations at top restaurants around the city are impossible to book. We're excited to partner with PRIMA to make this process much easier.",
            'author' => 'Edgar Gonzalez',
            'venue' => 'Continuum On South Beach'
        ],
        [
            'quote' => "PRIMA comes at the right time to help us deal with the onslaught of bots, fake reservations and cancellations.",
            'author' => 'Adam Borden',
            'venue' => 'Groot Hospitality Group'
        ],
        [
            'quote' => "Our concierge team is excited to use PRIMA to help book reservations for our guests as it is often very difficult to do so during the busy time of the season. We're excited to partner with PRIMA to also help fill our dining room.",
            'author' => 'Iria Urgell',
            'venue' => 'Aguamadera Hotel'
        ],
        [
            'quote' => "We've been waiting for a solution like PRIMA for a while to help book coveted reservations for our community. I'm very excited to begin using the platform.",
            'author' => 'Jorge Bargioni',
            'venue' => 'Murano Portofino'
        ],
        [
            'quote' => "PRIMA will make it much easier for us to book reservations for our residents. We consistently receive many requests that we cannot handle because reservations at top restaurants around the city are impossible to book. We're excited to partner with PRIMA to make this process much easier.",
            'author' => 'Edgar Gonzalez',
            'venue' => 'Continuum On South Beach'
        ],
        [
            'quote' => "PRIMA comes at the right time to help us deal with the onslaught of bots, fake reservations and cancellations.",
            'author' => 'Adam Borden',
            'venue' => 'Groot Hospitality Group'
        ],
        [
            'quote' => "Our concierge team is excited to use PRIMA to help book reservations for our guests as it is often very difficult to do so during the busy time of the season. We're excited to partner with PRIMA to also help fill our dining room.",
            'author' => 'Iria Urgell',
            'venue' => 'Aguamadera Hotel'
        ],
    ];
@endphp

<section class="px-0 pb-10 md:pb-16">
    <div class="max-w-full px-5 mx-auto w-full md:max-w-screen-xl">
        <div class="pb-8 text-center">
            <h2 class="text-2xl leading-tight pt-8">What Our Partners Say</h2>
            <p class="text-sm leading-normal pt-4">Success Stories from Restaurants & Concierges</p>
        </div>
        <div class="swiper testimonial-swiper">
            <div class="swiper-wrapper">
                @foreach ($testimonials as $testimonial)
                    <x-web.testimonial-item :testimonial="$testimonial" class="swiper-slide mb-8"/>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>
