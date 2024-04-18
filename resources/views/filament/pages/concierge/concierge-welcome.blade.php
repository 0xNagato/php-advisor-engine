<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Welcome to PRIMA!
        </x-slot>

        <x-slot name="headerEnd">
            <div class="text-sm text-gray-500">
                {{ auth()->user()->created_at->format('M j h:ia') }}
            </div>
        </x-slot>
        <div class="flex flex-col gap-4">
            <p>We are excited to have you as a part of our team when PRIMA fully launches. Restaurants are being
                onboarded
                now
                and we expect to be fully functioning in the coming weeks.</p>
            <p>So that you may fully experience the potential of PRIMA, we have created a demo concierge account for you
                to
                explore. Please click the link below and you will be taken to our demo site where you can try out the
                Reservation Hub as well as other features of PRIMA.</p>
            <p>Welcome aboard!</p>
            <p>We look forward to working with you.</p>

            @unless(Request::getHost() === 'demo.primavip.co')
                <x-filament::button tag="a" href="https://demo.primavip.co/login?email=concierge@primavip.co&password=demo2024" target="_blank">
                    Access Demo Account
                </x-filament::button>
            @endunless


            <p></p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
