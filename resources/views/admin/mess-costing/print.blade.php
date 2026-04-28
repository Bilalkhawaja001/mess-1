<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mess Costing Print</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .muted { color: #666; font-size: 12px; }
    </style>
</head>
<body onload="window.print()">
    <h2>Mess Costing Snapshot</h2>
    <div class="muted">Saved snapshot only. No billing, payment, or member ledger mutation performed.</div>
    <p><strong>Month:</strong> {{ $costing->month_cycle }}</p>
    <p><strong>Mess:</strong> {{ optional($costing->mess)->name ?? 'All Messes' }}</p>
    <table>
        <tr><th>Food Cost</th><td>{{ number_format((float)$costing->food_cost, 2) }}</td></tr>
        <tr><th>Gas Cost</th><td>{{ $costing->include_gas_cost ? number_format((float)$costing->gas_cost, 2) : 'Excluded' }}</td></tr>
        <tr><th>Salary Cost</th><td>{{ $costing->include_salary_cost ? number_format((float)$costing->salary_cost, 2) : 'Excluded' }}</td></tr>
        <tr><th>Other Expense</th><td>{{ number_format((float)$costing->other_cost, 2) }}</td></tr>
        <tr><th>Total Cost</th><td>{{ number_format((float)$costing->total_cost, 2) }}</td></tr>
        <tr><th>Member Count</th><td>{{ $costing->member_count }}</td></tr>
        <tr><th>Active Days Total</th><td>{{ number_format((float)$costing->active_days_total, 3) }}</td></tr>
        <tr><th>Cost Per Member</th><td>{{ number_format((float)$costing->cost_per_member, 2) }}</td></tr>
        <tr><th>Cost Per Day</th><td>{{ number_format((float)$costing->cost_per_day, 4) }}</td></tr>
    </table>
</body>
</html>
