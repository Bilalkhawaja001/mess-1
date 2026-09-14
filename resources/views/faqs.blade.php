@extends('layouts.auth')
@section('title', 'FAQs - Centralized Mess')
@section('auth_content')
<div style="max-width:820px;margin:0 auto;padding:28px 20px;line-height:1.7;color:#0f172a;">
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:.4rem;">Frequently Asked Questions</h1>
    <p style="color:#64748b;margin-bottom:1.6rem;">Common questions about Centralized Mess billing and the member app.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">What is Centralized Mess?</h2>
    <p>Centralized Mess is an online mess management service for registered members. It lets members view their mess bills, ledger, statements, payment records, and daily menu, and submit complaints through the member app and website.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">Who can use this service?</h2>
    <p>The service is only for registered mess members and authorized administration staff. It is not open to the general public.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">How do I view my mess bill?</h2>
    <p>Log in to the member app or website with your member credentials and open the Bill or Statement section to see your current bill and history.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">How can I pay my mess bill?</h2>
    <p>Mess bills are settled through the mess administration (for example bank transfer or cash), and members can upload payment proof through the app for verification. All amounts are shown in Pakistani Rupees (PKR).</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">What if my bill or payment record is incorrect?</h2>
    <p>Contact the mess administration with your member ID and details. After verification, records are reviewed and corrected where required.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">What is the refund and cancellation policy?</h2>
    <p>Please see our <a href="{{ url('/refund-and-cancellation-policy') }}" style="color:#0f3a73;font-weight:600;">Refund &amp; Cancellation Policy</a> page for full details.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">How is my personal data handled?</h2>
    <p>Member data is used only for mess operations. See our <a href="{{ url('/privacy') }}" style="color:#0f3a73;font-weight:600;">Privacy Policy</a> for how information is collected, stored, and used.</p>

    <h2 style="font-size:1.05rem;font-weight:700;margin:1.4rem 0 .4rem;">How do I contact support?</h2>
    <p>
        Email: <a href="mailto:admin@nodesky.pk" style="color:#0f3a73;font-weight:600;">admin@nodesky.pk</a><br>
        Phone: 0333-3457755, 0300-5467755<br>
        Address: Office No. 1, Plot No. 1838, Deh Kalokohar, Opp. SDO Office, Nooriabad, Thana Bula Khan, Jamshoro, Sindh, Pakistan
    </p>

    <p style="margin-top:2rem;">
        <a href="{{ url('/terms-and-conditions') }}" style="color:#64748b;margin-right:14px;">Terms &amp; Conditions</a>
        <a href="{{ url('/business-information') }}" style="color:#64748b;">Business &amp; Payment Info</a>
    </p>
    <p style="margin-top:1.4rem;color:#94a3b8;font-size:.8rem;">Powered by NodeSky Technologies</p>
</div>
@endsection
