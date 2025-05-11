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
            (b) "Non-Prime Time Reservation" a Reservation for a time that is designated as a
            non-prime time Reservation for Restaurant.
        </p>

        <p>
            (c) "Non-Prime Time Reservation Fee" means the amount that PRIMA charges Restaurant
            for a Non-Prime Time Reservation which shall be determined ahead of time by the Restaurant and shall be
            either:
        </p>
        <ul style="list-style-type: none; margin: 4px 0 8px 40px;">
            <li style="margin-bottom: 2px;">(i) A dollar amount per diner; or</li>
            <li>(ii) [X]% of the bill (excluding gratuity)</li>
        </ul>

        <p>
            (d) "Prime-Time Reservation Fee" means the amount PRIMA charges a customer for a Prime-Time Reservation.
        </p>

        <p>
            (e) "Platform Fee" means a fee equal to ten percent (10%) of the applicable Non-Prime
            Incentive Plan.
        </p>

        <p>
            (f) "Prime-Time Reservation" a Reservation for a time that is designated as prime-time for Restaurant.
        </p>

        <p>
            (g) "Qualified Reservation" means a Reservation at the Restaurant made by a customer on the Platform.
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

    <h2>5. Cancellation</h2>
    <p>
        Either PRIMA or the Restaurant may terminate this Agreement at any time by providing written notice to the other
        party. Upon termination:
    </p>
    <div class="section">
        <ul>
            <li>All fees due to PRIMA shall be paid by the Restaurant within fourteen (14) days of the cancellation
                date.</li>
            <li>Any fees owed to the Restaurant by PRIMA must be paid within fourteen (14) days of the calendar month in
                which the cancellation occurred.</li>
        </ul>
    </div>

    <div class="signature">
        <p>This Agreement has been electronically accepted by the undersigned on behalf of the parties.</p>

        <div class="signature-grid">
            <p>
                <span class="signature-label">Accepted By:</span>
                {{ $company_name }}
            </p>
            <p>
                <span class="signature-label">Date of Acceptance:</span>
                {{ $created_at->format('F j, Y') }}
            </p>
        </div>
    </div>
</body>

</html>
