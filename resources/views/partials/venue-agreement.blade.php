<div class="prose prose-sm max-w-none">
    <h1 class="text-2xl font-bold mb-6">RESTAURANT AGREEMENT</h1>

    <p class="mb-4">
        This Restaurant Agreement (this "Agreement") is entered into by and among PRIMA VIP INC, a Delaware
        corporation ("PRIMA"), and {{ $company_name }} ("Restaurant") or restaurant group.
    </p>

    <p class="mb-6">
        PRIMA connects customers with reservations at in-demand restaurants (collectively "Reservations")
        via PRIMA's platform (the "Platform"). The Restaurant desires to allow PRIMA to sell Reservations
        for the Restaurant on the Platform.
    </p>

    <h2 class="text-xl font-semibold mb-3">1. Definitions</h2>

    <div class="ml-4 mb-6">
        <p class="mb-2">
            (a) "Gross Prime-Time Reservation Fee" means the Prime-Time Reservation Fee less any applicable
            credit card or payment processor fees and any refunds.
        </p>

        <p class="mb-2">
            (b) "Non-Prime Time Reservation" a Reservation for a time that is designated as a non-prime
            time Reservation for Restaurant.
        </p>

        <p class="mb-2">
            (c) "Non-Prime Time Reservation Fee" means the amount that PRIMA charges Restaurant for a
            Non-Prime Time Reservation which shall be determined ahead of time by the Restaurant and shall be:
            @if ($use_non_prime_incentive)
                ${{ number_format($non_prime_per_diem, 2) }} per diner
            @else
                Not applicable as Restaurant has opted out of the non-prime incentive program
            @endif
        </p>

        <p class="mb-2">
            (d) "Prime-Time Reservation Fee" means the amount PRIMA charges a customer for a Prime-Time Reservation.
        </p>

        <p class="mb-2">
            (e) "Platform Fee" means a fee equal to ten percent (10%) of the applicable Non-Prime Incentive Plan.
        </p>

        <p class="mb-2">
            (f) "Prime-Time Reservation" a Reservation for a time that is designated as prime-time for Restaurant.
        </p>

        <p class="mb-2">
            (g) "Qualified Reservation" means a Reservation at the Restaurant made by a customer on the Platform.
        </p>
    </div>

    <h2 class="text-xl font-semibold mb-3">2. Commission</h2>
    <p class="mb-4">
        Subject to Section 3 below, PRIMA shall pay Restaurant a monthly commission for Qualified
        Reservations (the "Commission") in the amount equal to:
    </p>
    <ul class="list-disc ml-8 mb-6">
        <li>60% of the Gross Prime-Time Reservation Fees for such calendar month; less</li>
        <li>the Non-Prime Time Incentive Fees for such calendar month; less</li>
        <li>the Platform Fees for such calendar month; less</li>
        <li>applicable credit card processing fees incurred by PRIMA.</li>
    </ul>

    <h2 class="text-xl font-semibold mb-3">3. Commission Payment Terms</h2>
    <div class="ml-4 mb-6">
        <p class="mb-4">
            (a) Gross Prime-Time Reservation Fees become payable to Restaurant only at such times and only
            to the extent that PRIMA actually receives payment from customer for a Qualified Reservation.
            In the event the calculation in Section 2 above becomes a negative number (e.g., the fees due
            PRIMA exceed the fees due Restaurant), PRIMA requires Restaurant to pay the balance to PRIMA
            within fifteen (15) days' of calendar month end.
        </p>

        <p class="mb-4">
            (b) PRIMA will provide real-time access to a statement of total Commission payable to the
            Restaurant for a calendar month. PRIMA shall remit one aggregate payment of the total
            Commissions to Restaurant within fifteen (15) days after the end of the applicable calendar month.
        </p>
    </div>

    <h2 class="text-xl font-semibold mb-3">4. Covered Restaurants</h2>
    <p class="mb-4">
        The term 'Restaurant' in this Agreement refers collectively to the following restaurant(s)
        represented by the authorized signatory, up to a maximum of five (5) establishments. The
        undersigned represents and warrants that they have the authority to bind the restaurants listed below:
    </p>

    <div class="ml-4 mb-6">
        @foreach ($venue_names as $index => $name)
            <p class="mb-2">{{ $index + 1 }}. {{ $name }}</p>
        @endforeach
    </div>

    <div class="mt-8">
        <p class="mb-4">This Agreement has been electronically accepted by the undersigned on behalf of the parties.
        </p>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="font-semibold">Accepted By:</p>
                <p>{{ $first_name }} {{ $last_name }}</p>
            </div>
            <div>
                <p class="font-semibold">Date of Acceptance:</p>
                <p>{{ now()->format('F j, Y') }}</p>
            </div>
        </div>
    </div>
</div>
