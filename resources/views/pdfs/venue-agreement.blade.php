<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: serif;
            line-height: 1.6;
            color: #000000;
        }

        h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 24px;
        }

        h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        p {
            margin-bottom: 16px;
        }

        .section {
            margin-left: 16px;
            margin-bottom: 24px;
        }

        .signature {
            margin-top: 32px;
        }

        .signature-grid {
            margin-top: 16px;
        }

        .signature-label {
            font-weight: 600;
        }

        ul {
            list-style-type: disc;
            margin-left: 20px;
            margin-bottom: 16px;
        }

        li {
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    <h1>RESTAURANT AGREEMENT</h1>

    <p style="font-weight: bold;">
        This Restaurant Agreement (this "Agreement") is entered into by and among PRIMA VIP INC, a Delaware
        corporation ("PRIMA"), and {{ $company_name }} ("Restaurant") or restaurant group.
    </p>

    <p>
        PRIMA connects customers with reservations at in-demand restaurants (collectively "Reservations")
        via PRIMA's platform (the "Platform"). The Restaurant desires to allow PRIMA to sell Reservations
        for the Restaurant on the Platform.
    </p>

    <h2>1. Definitions</h2>
    <div class="section">
        <p>
            (a) "Gross Prime-Time Reservation Fee" means the Prime-Time Reservation Fee less any applicable
            credit card or payment processor fees and any refunds.
        </p>

        <p>
            (b) "Non-Prime Time Reservation" a Reservation for a time that is designated as a non-prime
            time Reservation for Restaurant.
        </p>

        <p>
            (c) "Non-Prime Time Reservation Fee" means the amount that PRIMA charges Restaurant for a
            Non-Prime Time Reservation which shall be determined ahead of time by the Restaurant and shall be:
            @if ($use_non_prime_incentive)
                ${{ number_format($non_prime_per_diem, 2) }} per diner
            @else
                Not applicable as Restaurant has opted out of the non-prime incentive program
            @endif
        </p>

        <p>
            (d) "Prime-Time Reservation Fee" means the amount PRIMA charges a customer for a Prime-Time Reservation.
        </p>

        <p>
            (e) "Platform Fee" means a fee equal to ten percent (10%) of the applicable Non-Prime Incentive Plan.
        </p>

        <p>
            (f) "Prime-Time Reservation" a Reservation for a time that is designated as prime-time for Restaurant.
        </p>

        <p>
            (g) "Qualified Reservation" means a Reservation at the Restaurant made by a customer on the Platform.
        </p>
    </div>

    <h2>2. Commission</h2>
    <p>
        Subject to Section 3 below, PRIMA shall pay Restaurant a monthly commission for Qualified
        Reservations (the "Commission") in the amount equal to:
    </p>
    <ul>
        <li>60% of the Gross Prime-Time Reservation Fees for such calendar month; less</li>
        <li>the Non-Prime Time Incentive Fees for such calendar month; less</li>
        <li>the Platform Fees for such calendar month; less</li>
        <li>applicable credit card processing fees incurred by PRIMA.</li>
    </ul>

    <h2>3. Commission Payment Terms</h2>
    <div class="section">
        <p>
            (a) Gross Prime-Time Reservation Fees become payable to Restaurant only at such times and only
            to the extent that PRIMA actually receives payment from customer for a Qualified Reservation.
            In the event the calculation in Section 2 above becomes a negative number (e.g., the fees due
            PRIMA exceed the fees due Restaurant), PRIMA requires Restaurant to pay the balance to PRIMA
            within fifteen (15) days' of calendar month end.
        </p>

        <p>
            (b) PRIMA will provide real-time access to a statement of total Commission payable to the
            Restaurant for a calendar month. PRIMA shall remit one aggregate payment of the total
            Commissions to Restaurant within fifteen (15) days after the end of the applicable calendar month.
        </p>
    </div>

    <h2>4. Covered Restaurants</h2>
    <p>
        The term 'Restaurant' in this Agreement refers collectively to the following restaurant(s)
        represented by the authorized signatory, up to a maximum of five (5) establishments. The
        undersigned represents and warrants that they have the authority to bind the restaurants listed below:
    </p>

    <div class="section">
        @foreach ($venue_names as $index => $name)
            <p>{{ $index + 1 }}. {{ $name }}</p>
        @endforeach
    </div>

    <div class="signature">
        <p>This Agreement has been electronically accepted by the undersigned on behalf of the parties.</p>

        <div class="signature-grid">
            <p>
                <span class="signature-label">Accepted By:</span>
                {{ $first_name }} {{ $last_name }}
            </p>
            <p>
                <span class="signature-label">Date of Acceptance:</span>
                {{ now()->format('F j, Y') }}
            </p>
        </div>
    </div>
</body>

</html>
