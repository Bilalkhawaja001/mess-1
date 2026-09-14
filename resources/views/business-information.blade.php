<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business & Payment Information - Centralized Mess</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #1f2937;
            line-height: 1.7;
        }
        .page {
            max-width: 900px;
            margin: 40px auto;
            padding: 40px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
        }
        .brand img {
            width: 150px;
            height: auto;
            margin-bottom: 22px;
        }
        h1 { color: #173f8a; margin-bottom: 5px; }
        h2 { color: #173f8a; margin-top: 28px; }
        .date { color: #64748b; margin-bottom: 28px; }
        a { color: #173f8a; }
        .info {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 18px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        @media (max-width: 700px) {
            .page { margin: 0; padding: 24px; border-radius: 0; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="brand">
        <a href="https://nodesky.pk">
            <img src="{{ asset('images/nodesky-logo.png') }}" alt="NodeSky Technologies">
        </a>
    </div>

    <h1>Business & Payment Information</h1>
    <p class="date">Last Updated: 04 September 2026</p>

    <div class="info">
        <strong>Service Name:</strong> Online Mess Services Billing<br>
        <strong>Technology Provider:</strong> NodeSky Technologies<br>
        <strong>Service Website:</strong>
        <a href="https://mess.bilalkhawaja.pk">mess.bilalkhawaja.pk</a><br>
        <strong>Company Website:</strong>
        <a href="https://nodesky.pk">nodesky.pk</a><br>
        <strong>Support / Complaints:</strong>
        <a href="mailto:admin@nodesky.pk">admin@nodesky.pk</a><br>
        <strong>Business Address:</strong> Office No. 1, Plot No. 1838, Deh Kalokohar, Opp. SDO Office, Nooriabad, Thana Bula Khan, Jamshoro, Sindh, Pakistan<br>
        <strong>Phone:</strong> 0333-3457755, 0300-5467755
    </div>

    <h2>1. About Centralized Mess</h2>
    <p>
        Centralized Mess is a mess management and billing service for registered
        Mess Members. The platform provides member account access, billing information,
        payment records and related administrative services.
    </p>

    <h2>2. User Eligibility</h2>
    <p>
        Access is intended for registered Mess Members and authorized
        administrative users. Users are responsible for maintaining the
        confidentiality of their login credentials.
    </p>

    <h2>3. Billing</h2>
    <p>
        Mess charges are generated according to applicable mess records,
        billing periods and administrative billing rules. Members can review
        their applicable charges through the Centralized Mess system.
    </p>

    <h2>4. Currency</h2>
    <p>
        All amounts displayed and collected through Centralized Mess are
        denominated in Pakistani Rupees (PKR).
    </p>

    <h2>5. Payment Methods</h2>
    <p>Available payment methods may include:</p>
    <ul>
        <li>Bank transfer</li>
        <li>Alfa Payment Gateway</li>
        <li>Other payment methods authorized by Centralized Mess administration</li>
    </ul>


    <h2>6. Online Payment Security</h2>
    <p>
        Where Alfa Payment Gateway is used, payment processing is performed
        through the payment gateway's secure payment environment. Members should
        not send card PINs, CVV codes, passwords or other sensitive banking
        credentials by email, telephone or messaging applications.
    </p>

    <h2>7. Payment Confirmation</h2>
    <p>
        A payment is considered successfully recorded after confirmation from
        the applicable payment channel and successful reconciliation with the
        Centralized Mess payment record.
    </p>

    <h2>8. Refunds & Cancellations</h2>
    <p>
        Refunds, duplicate payments, overpayments and billing disputes are
        handled according to our
        <a href="{{ url('/refund-and-cancellation-policy') }}">
            Refund & Cancellation Policy
        </a>.
    </p>

    <h2>9. Terms and Privacy</h2>
    <p>
        Use of the service is also subject to our
        <a href="{{ url('/terms-and-conditions') }}">Terms & Conditions</a>
        and
        <a href="{{ route('privacy') }}">Privacy Policy</a>.
    </p>

    <h2>10. Complaints and Support</h2>
    <p>
        For billing queries, payment complaints, refund requests or technical
        assistance:
    </p>

    <p>
        Email: <a href="mailto:admin@nodesky.pk">admin@nodesky.pk</a><br>
        Business Address: Office No. 1, Plot No. 1838, Deh Kalokohar, Opp. SDO Office, Nooriabad, Thana Bula Khan, Jamshoro, Sindh, Pakistan<br>
        Phone: 0333-3457755, 0300-5467755<br>
        Website: <a href="https://nodesky.pk">https://nodesky.pk</a>
    </p>

    <div class="footer">
        <strong>Powered by NodeSky Technologies</strong>
    </div>

</div>
</body>
</html>
