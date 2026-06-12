<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mess Member App - Data Deletion Request</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
            line-height: 1.6;
        }
        .wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 18px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
        }
        h1 {
            margin-top: 0;
            color: #0f172a;
            font-size: 30px;
        }
        h2 {
            margin-top: 28px;
            color: #111827;
            font-size: 20px;
        }
        ul, ol {
            padding-left: 22px;
        }
        .note {
            background: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 14px 16px;
            border-radius: 10px;
            margin-top: 18px;
        }
        .contact {
            font-weight: 700;
            color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Mess Member App - Data Deletion Request</h1>

        <p>
            This page explains how users of the Mess Member App can request deletion of their account or app-related data.
            Mess Member App is used by registered mess members and employees to view mess bills, ledger details,
            payment records, menu information, complaints and profile details.
        </p>

        <h2>How to request data deletion</h2>
        <ol>
            <li>Send an email to <span class="contact">admin@bilalkhawaja.pk</span></li>
            <li>Use the subject: <strong>Mess Member App Data Deletion Request</strong></li>
            <li>Include your Employee ID / Member ID, full name, department and registered mobile number.</li>
            <li>The Mess Administration will verify your identity before processing the request.</li>
        </ol>

        <h2>Data that may be deleted</h2>
        <ul>
            <li>App login access or session/token data</li>
            <li>Profile data used only for app access, where deletion is allowed</li>
            <li>Complaint records submitted through the app, where deletion is allowed</li>
            <li>Payment proof screenshots submitted through the app, where deletion is allowed</li>
        </ul>

        <h2>Data that may be retained</h2>
        <p>
            Some data may be retained where required for company administration, audit, accounting, legal,
            operational or record-keeping purposes.
        </p>
        <ul>
            <li>Mess billing records</li>
            <li>Ledger/account records</li>
            <li>Payment records</li>
            <li>Attendance and monthly mess records</li>
            <li>Records required for audit, accounting or administrative purposes</li>
        </ul>

        <h2>Retention period</h2>
        <p>
            App-only access data may be deleted after verification and processing of the request. Mess billing,
            payment, ledger and attendance records may be retained as required for company records, audit and
            operational purposes.
        </p>

        <div class="note">
            For any data deletion request or privacy-related question, please contact:
            <br>
            <span class="contact">admin@bilalkhawaja.pk</span>
        </div>

        <div class="footer">
            Last updated: {{ date('F d, Y') }}
        </div>
    </div>
</div>
</body>
</html>
