<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Guest Meal Report</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }

  .toolbar {
    text-align: center; padding: 12px; background: #f2f4f7; border-bottom: 1px solid #ccc;
  }
  .toolbar button {
    font-size: 14px; padding: 8px 20px; margin: 0 4px; cursor: pointer;
    border: 1px solid #2563eb; background: #2563eb; color: #fff; border-radius: 4px;
  }
  .toolbar button.secondary { background: #fff; color: #2563eb; }

  .sheet { width: 198mm; margin: 8px auto; padding: 8mm; background: #fff; border: 1px solid #ddd; }
  .report-head { text-align: center; margin-bottom: 5mm; }
  .report-head h1 { font-size: 14pt; font-weight: 700; }
  .report-head .range { font-size: 8.5pt; margin-top: 1.5mm; }

  table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7.6pt; }
  th, td {
    border: 1px solid #cfcfcf; padding: 7px 4px; vertical-align: top; line-height: 1.5;
    word-break: break-word; text-align: left;
  }
  th { background: #eef2f7; font-weight: 700; }
  td.num, th.num, td.ctr, th.ctr { text-align: center; }
  tfoot td { font-weight: 700; background: #f7f9fc; font-size: 8.2pt; }

  .col-date { width: 11%; }
  .col-name { width: 24%; }
  .col-from { width: 21%; }
  .col-type { width: 11%; }
  .col-qty  { width: 9%; }
  .col-rate { width: 11%; }
  .col-amt  { width: 13%; }

  @media print {
    .toolbar { display: none !important; }
    @page { size: A4 portrait; margin: 10mm 8mm 12mm 8mm; }
    .sheet { width: auto; margin: 0; padding: 0; border: 0; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr { page-break-inside: avoid; }
  }
</style>
</head>
<body>

@php
    $rangeText = ($fromDate || $toDate)
        ? trim(($fromDate ?: '—') . ' to ' . ($toDate ?: '—'))
        : 'All Dates';
@endphp

<div class="toolbar">
  <button onclick="window.print()">🖨 Print</button>
  <button class="secondary" onclick="window.close()">Close</button>
</div>

<div class="sheet">
  <div class="report-head">
    <h1>Guest Meal Report</h1>
    <div class="range">Date Range: {{ $rangeText }}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="col-date">Meal Date</th>
        <th class="col-name">Guest Name</th>
        <th class="col-from">Company / Came From</th>
        <th class="col-type ctr">Meal Type</th>
        <th class="col-qty num">Qty</th>
        <th class="col-rate num">Rate</th>
        <th class="col-amt num">Amount</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $m)
        <tr>
          <td>{{ \Illuminate\Support\Carbon::parse($m->meal_date)->format('d-M-Y') }}</td>
          <td>
            {{ optional($m->guest)->name ?? '—' }}
            @if(optional($m->guest)->guest_code)
              <span style="color:#666;">({{ $m->guest->guest_code }})</span>
            @endif
          </td>
          <td>{{ $m->came_from ?: (optional($m->guest)->came_from ?: '—') }}</td>
          <td class="ctr">{{ strtoupper($m->meal_type) }}</td>
          <td class="num">{{ number_format((float) $m->quantity, 2) }}</td>
          <td class="num">
            @if($m->rate_missing) <span style="color:#c00;">N/A</span>
            @else {{ number_format((float) $m->rate_display, 2) }} @endif
          </td>
          <td class="num">
            @if($m->rate_missing) <span style="color:#c00;">—</span>
            @else {{ number_format((float) $m->amount_display, 2) }} @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:14px;">No records found for this range.</td></tr>
      @endforelse
    </tbody>
    @if($rows->count())
    <tfoot>
      <tr>
        <td colspan="4" class="num">GRAND TOTAL</td>
        <td class="num">{{ number_format((float) $qtyTotal, 2) }}</td>
        <td></td>
        <td class="num">{{ number_format((float) $grandTotal, 2) }}</td>
      </tr>
    </tfoot>
    @endif
  </table>
</div>

</body>
</html>
