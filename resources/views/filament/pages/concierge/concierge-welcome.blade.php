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

            <p class="text-sm font-semibold">Please use the following credentials to access our demo server.</p>
            <p class="grid grid-cols-2 p-4 -mt-1 text-sm font-semibold border rounded-lg shadow bg-indigo-50">
                <span>Email: </span>
                <bold class="text-indigo-500">concierge@primavip.co</bold>
                <span>Password: </span>
                <bold class="text-indigo-500">demo2024</bold>
            </p>

            <x-filament::button tag="a" href="https://demo.primavip.co/admin" target="_blank">
                Access Demo Account
            </x-filament::button>





            <p></p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
