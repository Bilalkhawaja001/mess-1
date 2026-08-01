@extends('layouts.app')
@section('content')
{{-- STATEMENT_REDESIGN_V2_20260801 --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
.stm-wrap{--bg:#FAF9F7;--ink:#1A1815;--ink2:#6B6560;--ink3:#9C9691;--line:#E9E5E0;--line2:#F2EFEB;--card:#FFFFFF;--accent:#8B5E34;--green:#166534;--red:#B4231F;--hover:#FBF9F6;background:var(--bg);font-family:'Inter',system-ui,sans-serif;color:var(--ink);padding:20px 0 48px;-webkit-font-smoothing:antialiased}
.stm-wrap *{box-sizing:border-box}
.stm-wrap .stm-shell{max-width:100%;margin:0;padding:0 32px;display:flex;flex-direction:column;gap:18px}
.stm-wrap .stm-num{font-variant-numeric:tabular-nums;font-feature-settings:'tnum'}

/* toolbar */
.stm-wrap .stm-bar{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end}
.stm-wrap .stm-field{display:flex;flex-direction:column;gap:6px}
.stm-wrap .stm-field.grow{flex:1;min-width:220px}
.stm-wrap .stm-lbl{font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--ink3)}
.stm-wrap .stm-in{height:38px;padding:0 12px;border:1px solid var(--line);border-radius:8px;background:#fff;font-size:13.5px;color:var(--ink);outline:none;transition:border-color .15s}
.stm-wrap .stm-in:focus{border-color:var(--accent)}
.stm-wrap .stm-in[type=month]{min-width:150px}
.stm-wrap .stm-actions{display:flex;gap:8px;flex-wrap:wrap;margin-left:auto}
.stm-wrap .stm-btn{height:38px;padding:0 16px;border-radius:8px;font-size:13px;font-weight:600;border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.15s;text-decoration:none}
.stm-wrap .stm-btn-primary{background:var(--ink);color:#fff}
.stm-wrap .stm-btn-primary:hover{background:#000}
.stm-wrap .stm-btn-green{background:var(--green);color:#fff}
.stm-wrap .stm-btn-green:hover{filter:brightness(.92)}
.stm-wrap .stm-btn-ghost{background:#fff;color:var(--ink2);border-color:var(--line)}
.stm-wrap .stm-btn-ghost:hover{background:var(--hover)}

/* statement sheet */
.stm-wrap .stm-sheet{background:var(--card);border:1px solid var(--line);border-radius:12px;overflow:hidden}
.stm-wrap .stm-accent{height:3px;background:linear-gradient(90deg,var(--accent),#C89B6A)}
.stm-wrap .stm-body{padding:32px 36px}
.stm-wrap .stm-head{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:20px;border-bottom:1px solid var(--line)}
.stm-wrap .stm-title{font-size:21px;font-weight:700;letter-spacing:-.01em;margin:0}
.stm-wrap .stm-sub{font-size:12.5px;color:var(--ink2);margin-top:3px}
.stm-wrap .stm-gen{font-size:11.5px;color:var(--ink3)}

/* member grid */
.stm-wrap .stm-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:20px 28px;padding:24px 0;border-bottom:1px solid var(--line)}
.stm-wrap .stm-meta .k{font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink3);margin-bottom:4px}
.stm-wrap .stm-meta .v{font-size:14px;font-weight:500;color:var(--ink)}

/* kpi strip */
.stm-wrap .stm-kpis{display:grid;grid-template-columns:repeat(4,1fr);border:1px solid var(--line);border-radius:10px;overflow:hidden;margin:24px 0}
.stm-wrap .stm-kpi{padding:16px 18px;border-right:1px solid var(--line)}
.stm-wrap .stm-kpi:last-child{border-right:none;background:#FBFAF8}
.stm-wrap .stm-kpi .k{font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink3);margin-bottom:8px}
.stm-wrap .stm-kpi .val{font-size:20px;font-weight:700;letter-spacing:-.01em}
.stm-wrap .stm-kpi.neg .val{color:var(--red)}

/* table */
.stm-wrap .stm-tbl-wrap{border:1px solid var(--line);border-radius:10px;overflow:hidden}
.stm-wrap table.stm-tbl{width:100%;border-collapse:collapse;font-size:13px}
.stm-wrap .stm-tbl thead th{background:#FBFAF8;padding:11px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink2);text-align:left;border-bottom:1px solid var(--line)}
.stm-wrap .stm-tbl thead th.r{text-align:right}
.stm-wrap .stm-tbl tbody td{padding:11px 14px;border-bottom:1px solid var(--line2);color:var(--ink);vertical-align:middle}
.stm-wrap .stm-tbl tbody td.r{text-align:right}
.stm-wrap .stm-tbl tbody tr:last-child td{border-bottom:none}
.stm-wrap .stm-tbl tbody tr:hover td{background:var(--hover)}
.stm-wrap .stm-muted{color:var(--ink3)}
.stm-wrap .stm-pill{display:inline-block;padding:2px 9px;border-radius:100px;font-size:11px;font-weight:600;background:#F2EFEB;color:var(--ink2)}
.stm-wrap .stm-pill.pay{background:#EAF3EC;color:var(--green)}
.stm-wrap .stm-rev{border:none;background:none;color:var(--red);font-size:11px;font-weight:600;cursor:pointer;padding:0;margin-left:8px;text-decoration:underline;text-underline-offset:2px}
.stm-wrap .stm-empty{padding:48px;text-align:center;color:var(--ink3);font-size:13.5px}

.stm-wrap .stm-foot{text-align:center;padding-top:20px;margin-top:24px;border-top:1px solid var(--line);font-size:11.5px;color:var(--ink3)}
@media(max-width:820px){.stm-wrap .stm-meta{grid-template-columns:repeat(2,1fr)}.stm-wrap .stm-kpis{grid-template-columns:repeat(2,1fr)}.stm-wrap .stm-kpi:nth-child(2){border-right:none}.stm-wrap .stm-body{padding:22px}}

/* FILTER_BALANCE_20260801 */
.stm-wrap .stm-field.grow{flex:0 0 260px;min-width:260px;max-width:260px}
.stm-wrap .stm-actions{gap:10px}
.stm-wrap .stm-actions .stm-btn{height:38px;padding:0 22px;font-size:13.5px;min-width:96px;justify-content:center}
/* FILTER_BALANCE_20260801 */
.stm-wrap .stm-field.grow{flex:0 0 260px;min-width:260px;max-width:260px}
.stm-wrap .stm-actions{gap:10px}
.stm-wrap .stm-actions .stm-btn{height:38px;padding:0 22px;font-size:13.5px;min-width:96px;justify-content:center}
/* COMPACT_TUNE_20260801 */
.stm-wrap{padding:12px 0 28px}
.stm-wrap .stm-shell{gap:12px}
.stm-wrap .stm-bar{padding:12px 14px;gap:12px}
.stm-wrap .stm-in{height:34px;font-size:13px}
.stm-wrap .stm-btn{height:34px;padding:0 13px;font-size:12.5px}
.stm-wrap .stm-body{padding:20px 24px}
.stm-wrap .stm-head{padding-bottom:14px}
.stm-wrap .stm-title{font-size:18px}
.stm-wrap .stm-meta{gap:12px 24px;padding:16px 0}
.stm-wrap .stm-meta .v{font-size:13px}
.stm-wrap .stm-kpis{margin:16px 0}
.stm-wrap .stm-kpi{padding:11px 14px}
.stm-wrap .stm-kpi .val{font-size:17px}
.stm-wrap .stm-kpi .k{margin-bottom:5px}
.stm-wrap .stm-tbl thead th{padding:8px 12px}
.stm-wrap .stm-tbl tbody td{padding:7px 12px;font-size:12.5px}
.stm-wrap .stm-foot{padding-top:14px;margin-top:16px}
</style>

<div class="stm-wrap compact-statement-page">
  <div class="stm-shell">

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.statement.index') }}" class="stm-bar statement-filter-grid">
      <div class="stm-field grow">
        <label class="stm-lbl" for="member_lookup">Member Lookup</label>
        <input class="stm-in" id="member_lookup" name="member_lookup" list="statementMemberLookupList" placeholder="Member ID / Name / Department / Mobile" autocomplete="off" type="text" value="{{ $memberLookup ?? '' }}"/>
        <datalist id="statementMemberLookupList">
          @foreach(($memberLookupSuggestions ?? $members) as $m)
            <option value="{{ $m->member_code }} - {{ $m->name }}">{{ $m->department_name ?? '' }} @if(!empty($m->mobile_number)) | {{ $m->mobile_number }} @endif</option>
          @endforeach
        </datalist>
        @if(($memberLookupNoResults ?? false))<span class="stm-lbl" style="color:var(--red)">No matching member found.</span>@endif
      </div>
      <div class="stm-field"><label class="stm-lbl" for="single_month">Single Month</label><input class="stm-in" id="single_month" name="single_month" type="month" value="{{ $singleMonth }}"/></div>
      <div class="stm-field"><label class="stm-lbl" for="from_month">From Month</label><input class="stm-in" id="from_month" name="from_month" type="month" value="{{ $fromMonth }}"/></div>
      <div class="stm-field"><label class="stm-lbl" for="to_month">To Month</label><input class="stm-in" id="to_month" name="to_month" type="month" value="{{ $toMonth }}"/></div>
      <div class="stm-actions">
        <button class="stm-btn stm-btn-primary" type="submit">View</button>
        <button class="stm-btn stm-btn-green" name="export" value="csv" type="submit">Excel</button>
        <a class="stm-btn stm-btn-ghost" href="{{ route('admin.statement.index') }}">Clear</a>
        <button class="stm-btn stm-btn-primary" onclick="printStatementOnly()" type="button">Print</button>
      </div>
    </form>

    {{-- Statement Sheet --}}
    <div class="stm-sheet statement-print">
      <div class="stm-accent"></div>
      <div class="stm-body">

        <div class="stm-head">
          <div>
            <h1 class="stm-title">Mess Statement</h1>
            <div class="stm-sub">Member Account Statement</div>
          </div>
          <div class="stm-gen">Generated: {{ now()->format('Y-m-d') }}</div>
        </div>

        <div class="stm-meta">
          <div><div class="k">Member ID</div><div class="v">{{ $member->member_code ?? '-' }}</div></div>
          <div><div class="k">Name</div><div class="v">{{ $member->name ?? '-' }}</div></div>
          <div><div class="k">Department</div><div class="v">{{ $member->department_name ?? '-' }}</div></div>
          <div><div class="k">Mess</div><div class="v">{{ $messName }}</div></div>
          <div><div class="k">Join Date</div><div class="v">{{ $member->join_date ?? '-' }}</div></div>
          <div><div class="k">Leave Date</div><div class="v">{{ $member->leave_date ?? '-' }}</div></div>
          <div style="grid-column:1/-1"><div class="k">Statement Period</div><div class="v">{{ $fromMonth }} &nbsp;to&nbsp; {{ $toMonth }}</div></div>
        </div>

        <div class="stm-kpis">
          <div class="stm-kpi"><div class="k">Opening Balance</div><div class="val stm-num">{{ number_format($openingBalance, 2) }}</div></div>
          <div class="stm-kpi"><div class="k">Total Debit</div><div class="val stm-num">{{ number_format($totalDebit, 2) }}</div></div>
          <div class="stm-kpi"><div class="k">Total Credit</div><div class="val stm-num">{{ number_format($totalCredit, 2) }}</div></div>
          <div class="stm-kpi {{ $closingBalance < 0 ? 'neg' : '' }}"><div class="k">Closing Balance</div><div class="val stm-num">{{ number_format($closingBalance, 2) }}</div></div>
        </div>

        <div class="stm-tbl-wrap">
          <table class="stm-tbl statement-table-compact">
            <thead>
              <tr>
                <th>Month</th><th>Pay Date</th><th class="r">Days</th><th class="r">Rate/Day</th>
                <th class="r">Amount</th><th>Ref Type</th><th>Ref ID</th>
                <th class="r">Debit</th><th class="r">Credit</th><th class="r">Balance</th>
              </tr>
            </thead>
            <tbody class="stm-num">
              @forelse($rows as $row)
              <tr>
                <td>{{ $row->month }}</td>
                <td class="stm-muted">{{ $row->payment_date !== '' ? $row->payment_date : '—' }}</td>
                <td class="r">{{ $row->days !== '' ? $row->days : '—' }}</td>
                <td class="r">{{ $row->rate_per_day !== '' ? number_format((float) $row->rate_per_day, 2) : '—' }}</td>
                <td class="r">{{ number_format((float) $row->total_amount, 2) }}</td>
                <td><span class="stm-pill {{ $row->ref_type === 'PAYMENT' ? 'pay' : '' }}">{{ $row->ref_type }}</span></td>
                <td>{{ $row->ref_id }}@if($row->ref_type === 'PAYMENT' && in_array($row->payment_status, ['APPROVED','RECONCILED','SUCCESS']))<form method="POST" action="{{ route('admin.payments.reverse', $row->ref_id) }}" style="display:inline" onsubmit="var rsn=prompt('Reverse payment #{{ $row->ref_id }} (amount {{ number_format((float) $row->credit, 2) }})?\n\nEnter reason (min 5 chars):'); if(rsn===null){return false;} if(rsn.trim().length<5){alert('Reason must be at least 5 characters.');return false;} this.reason.value=rsn; return true;">@csrf<input type="hidden" name="reason" value=""><button type="submit" class="stm-rev">Reverse</button></form>@endif</td>
                <td class="r">{{ (float) $row->debit > 0 ? number_format((float) $row->debit, 2) : '—' }}</td>
                <td class="r">{{ (float) $row->credit > 0 ? number_format((float) $row->credit, 2) : '—' }}</td>
                <td class="r">{{ number_format((float) $row->running_balance, 2) }}</td>
              </tr>
              @empty
              <tr><td colspan="10" class="stm-empty">No statement rows found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="stm-foot">This is a system-generated statement and does not require any signature or stamp.</div>
      </div>
    </div>

  </div>
</div>

<script>
/* SAFE_STATEMENT_PRINT_WINDOW_20260615 */
function printStatementOnly() {
    const statement = document.querySelector('.statement-print');
    if (!statement) {
        window.print();
        return;
    }

    const printWindow = window.open('', '_blank', 'width=900,height=1200');
    if (!printWindow) {
        window.print();
        return;
    }

    const html = `
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Mess Statement</title>
<style>
@page {
    size: A4 portrait;
    margin: 6mm;
}
* {
    box-sizing: border-box;
}
html, body {
    margin: 0;
    padding: 0;
    background: #fff;
    color: #111827;
    font-family: Arial, Helvetica, sans-serif;
}
body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.statement-print {
    width: 198mm;
    max-width: 198mm;
    margin: 0 auto;
    padding: 3mm;
    border: 1px solid #d8dee8;
    border-radius: 4px;
    background: #fff;
    font-size: 7.8px;
    line-height: 1.18;
}
.d-flex { display: flex; }
.justify-content-between { justify-content: space-between; }
.align-items-start { align-items: flex-start; }
h3 {
    font-size: 13px;
    margin: 0 0 4px 0;
    font-weight: 700;
}
.text-muted { color: #53657f; }
.small { font-size: 7px; }
hr {
    border: 0;
    border-top: 1px solid #b9c3d0;
    margin: 5px 0;
}
.row {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    margin: 0 0 3px 0;
}
.col-md-2 { width: 16.666666%; }
.col-md-3 { width: 25%; padding-right: 3px; }
.col-md-4 { width: 33.333333%; }
.col-md-10 { width: 83.333333%; }
.fw-semibold { font-weight: 600; }
.fw-normal { font-weight: 400; }
.fw-bold { font-weight: 700; }
.mb-1, .mb-2, .mb-3 { margin-bottom: 3px; }
.border.rounded.p-2 {
    border: 1px solid #d8dee8;
    border-radius: 4px;
    padding: 3px 5px;
}
.h5 {
    font-size: 11px;
    margin: 0;
    font-weight: 700;
}
.table-responsive {
    width: 100%;
    overflow: visible;
}
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 7.3px;
}
th, td {
    border: 1px solid #cfd6df;
    padding: 2.1px 2.3px;
    white-space: nowrap;
    overflow: hidden;
    vertical-align: middle;
}
th {
    background: #f3f6f9;
    color: #53657f;
    font-size: 7.1px;
    font-weight: 700;
}
th:nth-child(1), td:nth-child(1) { width: 8.5%; }
th:nth-child(2), td:nth-child(2) { width: 12%; }
th:nth-child(3), td:nth-child(3) { width: 5.5%; }
th:nth-child(4), td:nth-child(4) { width: 9.5%; }
th:nth-child(5), td:nth-child(5) { width: 11.5%; }
th:nth-child(6), td:nth-child(6) { width: 15%; }
th:nth-child(7), td:nth-child(7) { width: 7%; }
th:nth-child(8), td:nth-child(8) { width: 9%; }
th:nth-child(9), td:nth-child(9) { width: 10%; }
th:nth-child(10), td:nth-child(10) { width: 12%; }
.text-center { text-align: center; }
.mt-3 { margin-top: 6px; }
</style>
</head>
<body>
${statement.outerHTML}
<script>
window.onload = function () {
    setTimeout(function () {
        window.print();
        window.close();
    }, 250);
};
<\/script>
</body>
</html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
</script>
@endsection
