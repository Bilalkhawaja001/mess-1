<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Mess - Bill Statement ({{ $monthCycle }})</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
body { font-family: "Inter", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 10pt; line-height: 1.5; color: #000; background: #e5e7eb; display: flex; justify-content: center; padding: 2rem; }
.document-container { width: 210mm; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1); padding: 14mm; display: block; }
@media print {
  body { padding: 0; background: #fff; display: block; }
  .document-container { width: 100%; min-height: auto; box-shadow: none; padding: 0; }
  @page { size: A4; margin: 12mm 14mm; }
  .no-print { display: none !important; }
}
.doc-strip { display: flex; justify-content: space-between; font-size: 8pt; color: #333; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 14px; }
.report-header { text-align: center; margin-bottom: 14px; }
.org-title { font-family: Georgia, "Times New Roman", serif; font-size: 18pt; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 5px; }
.doc-title { font-family: Georgia, "Times New Roman", serif; font-size: 12pt; font-weight: 400; font-style: italic; color: #333; }
.meta-bar { display: flex; justify-content: space-between; align-items: center; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 7px 5px; margin-bottom: 18px; font-size: 8.5pt; }
.meta-item { display: flex; gap: 6px; }
.meta-label { font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.financial-statement { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; margin-bottom: 0; }
.financial-statement th, .financial-statement td { padding: 5px 5px; vertical-align: bottom; }
.financial-statement th { text-align: right; font-size: 8pt; font-weight: 600; text-transform: uppercase; color: #333; border-bottom: 1px solid #000; padding-bottom: 10px; }
.financial-statement th.col-desc { text-align: left; }
.col-amt { text-align: right; width: 180px; }
.section-header td { font-family: Georgia, "Times New Roman", serif; font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; padding-top: 14px; padding-bottom: 6px; }
.line-item td { font-size: 10pt; border-bottom: 1px dotted #ccc; }
.line-item .col-desc { padding-left: 15px; }
.subtotal-row td { font-weight: 600; padding-top: 8px; padding-bottom: 8px; border-top: 1px solid #000; }
.grand-total-row td.col-desc { font-family: Georgia, "Times New Roman", serif; }
.grand-total-row td { font-weight: 700; font-size: 11pt; padding-top: 10px; padding-bottom: 10px; text-transform: uppercase; border-top: 2px solid #000; border-bottom: 4px double #000; }
.signatures-area { margin-top: auto; padding-top: 55px; display: flex; justify-content: space-between; gap: 24px; page-break-inside: avoid; }
.sig-box { flex: 1; text-align: center; }
.sig-line { border-bottom: 1px solid #000; height: 38px; margin-bottom: 5px; }
.sig-title { font-size: 8.5pt; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.document-footer { margin-top: 22px; display: flex; justify-content: space-between; font-size: 8pt; color: #333; border-top: 1px solid #ccc; padding-top: 12px; page-break-inside: avoid; }
.no-print { position: fixed; top: 12px; right: 12px; }
.no-print button { padding: 8px 18px; font-size: 13px; cursor: pointer; }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">Print</button></div>
<div class="document-container">
  <div class="doc-strip">
    <span>Admin Mess Bill Statement &mdash; Cycle {{ $monthCycle }}</span>
    <span>Page 1 of 1</span>
  </div>
  <header class="report-header">
    <div class="org-title">Admin Mess</div>
    <div class="doc-title">Bill Statement</div>
  </header>

  <div class="meta-bar">
    <div class="meta-item"><span class="meta-label">Month Cycle:</span><span>{{ $monthCycle }}</span></div>
    <div class="meta-item"><span class="meta-label">Billing Period:</span><span>{{ $rangeStart->format('d M Y') }} &ndash; {{ $rangeEnd->format('d M Y') }}</span></div>
    <div class="meta-item"><span class="meta-label">Printed on:</span><span>{{ now()->format('d M Y h:i A') }}</span></div>
  </div>

  <table class="financial-statement">
    <thead><tr><th class="col-desc">Description</th><th class="col-amt">Amount (PKR)</th></tr></thead>
    <tbody>
      <tr class="section-header"><td colspan="2">Working</td></tr>
      <tr class="line-item"><td class="col-desc">Total Expenses</td><td class="col-amt">{{ number_format($totalExpenses, 2) }}</td></tr>
      <tr class="line-item"><td class="col-desc">Less: Guest Amount</td><td class="col-amt">({{ number_format($guestAmount, 2) }})</td></tr>
      <tr class="subtotal-row"><td class="col-desc">Balance Amount</td><td class="col-amt">{{ number_format($balanceAmount, 2) }}</td></tr>
      <tr class="line-item"><td class="col-desc">Less: Worker/Employees ({{ number_format($workerDays) }} days &times; {{ number_format($contractorRate, 2) }})</td><td class="col-amt">({{ number_format($workerAmount, 2) }})</td></tr>
      <tr class="subtotal-row"><td class="col-desc">Net Balance</td><td class="col-amt">{{ number_format($netBalance, 2) }}</td></tr>

      <tr class="section-header"><td colspan="2">Payable</td></tr>
      <tr class="line-item"><td class="col-desc">Company Share (50% of Net Balance)</td><td class="col-amt">{{ number_format($companyPaid, 2) }}</td></tr>
      <tr class="line-item"><td class="col-desc">Add: Guest Amount</td><td class="col-amt">{{ number_format($guestAmount, 2) }}</td></tr>
      <tr class="line-item"><td class="col-desc">Add: LPTL &amp; Wind Power employees &amp; guest Amount</td><td class="col-amt">{{ number_format($workerAmount, 2) }}</td></tr>
      <tr class="grand-total-row"><td class="col-desc">Total Amount to be Paid</td><td class="col-amt">{{ number_format($totalAmount, 2) }}</td></tr>
    </tbody>
  </table>

</div>
</body>
</html>
