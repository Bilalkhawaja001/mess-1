@extends('layouts.app')
@section('content')
<div class="container-fluid py-3" style="font-family:'Inter',system-ui,sans-serif">
  <h4 class="mb-1">Cost Allocation Preview <span class="text-muted small">(read-only, nothing saved)</span></h4>
  <p class="text-muted small mb-3">Month: {{ $monthCycle }} &nbsp;|&nbsp; Range: {{ $from }} → {{ $to }}</p>

  <form method="GET" class="row g-2 mb-4">
    <div class="col-md-3"><label class="form-label small mb-1">Month cycle (YYYY-MM)</label>
      <input type="text" name="month_cycle" value="{{ $monthCycle }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-1">Executive weight</label>
      <input type="number" step="0.01" name="executive_weight" value="{{ $execWeight }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-1">Centralized weight</label>
      <input type="number" step="0.01" name="centralized_weight" value="{{ $centWeight }}" class="form-control form-control-sm"></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-sm w-100">Recalculate</button></div>
  </form>

  @php $fmt = fn($n) => number_format((float)$n, 2); @endphp

  <div class="row">
    <div class="col-lg-6">
      <div class="card shadow-sm mb-3">
        <div class="card-header"><b>Cost Waterfall</b></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <tbody>
              <tr><td>Purchase Total (net GRN)</td><td class="text-end">{{ $fmt($purchaseTotal) }}</td></tr>
              <tr><td>− Guest Amount</td><td class="text-end">{{ $fmt($guestAmount) }}</td></tr>
              <tr class="table-light"><td><b>Balance after Guest</b></td><td class="text-end"><b>{{ $fmt($balanceAfterGuest) }}</b></td></tr>
              <tr><td>Attendance — Executive</td><td class="text-end">{{ $execAtt }}</td></tr>
              <tr><td>Attendance — Centralized</td><td class="text-end">{{ $centAtt }}</td></tr>
              <tr><td>Attendance — Contractors</td><td class="text-end">{{ $contractorAtt }}</td></tr>
              <tr><td>Total Attendance</td><td class="text-end">{{ $totalAttendance }}</td></tr>
              <tr><td>Flat per-day (balance ÷ total att)</td><td class="text-end">{{ $fmt($flatPerDay) }}</td></tr>
              <tr><td>− Contractor Amount</td><td class="text-end">{{ $fmt($contractorAmount) }}</td></tr>
              <tr class="table-light"><td><b>Mess Pool (Exec+Cent)</b></td><td class="text-end"><b>{{ $fmt($messPool) }}</b></td></tr>
              <tr><td>Member Half (50%)</td><td class="text-end">{{ $fmt($memberHalf) }}</td></tr>
              <tr><td>Company Half (50%)</td><td class="text-end">{{ $fmt($companyHalf) }}</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-header"><b>Weighted Member Rates</b></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <tbody>
              <tr><td>Weighted Units (exec×{{ $execWeight }} + cent×{{ $centWeight }})</td><td class="text-end">{{ $fmt($weightedUnits) }}</td></tr>
              <tr><td>Per Unit (memberHalf ÷ weightedUnits)</td><td class="text-end">{{ $fmt($perUnit) }}</td></tr>
              <tr><td><b>Executive rate/day</b></td><td class="text-end"><b>{{ $fmt($execRatePerDay) }}</b></td></tr>
              <tr><td><b>Centralized rate/day</b></td><td class="text-end"><b>{{ $fmt($centRatePerDay) }}</b></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-header"><b>Company Payable (preview)</b></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <tbody>
              <tr><td>Company Half</td><td class="text-end">{{ $fmt($companyHalf) }}</td></tr>
              <tr><td>+ Guest Amount</td><td class="text-end">{{ $fmt($guestAmount) }}</td></tr>
              <tr><td>+ Contractor Amount (all company-paid, Phase 1)</td><td class="text-end">{{ $fmt($contractorAmount) }}</td></tr>
              <tr class="table-light"><td><b>Company Payable</b></td><td class="text-end"><b>{{ $fmt($companyPayable) }}</b></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card shadow-sm mb-3 {{ $reconPass ? 'border-success' : 'border-danger' }}">
        <div class="card-body">
          <b>Reconciliation:</b>
          memberHalf + companyHalf + contractor + guest = {{ $fmt($reconTarget) }}
          vs Purchase Total {{ $fmt($purchaseTotal) }} →
          @if($reconPass)<span class="badge bg-success">PASS</span>@else<span class="badge bg-danger">FAIL</span>@endif
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between"><b>Per-Member Preview</b>
          <span class="text-muted small">Total: {{ $fmt($memberPreviewTotal) }} (should ≈ Member Half {{ $fmt($memberHalf) }})</span></div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:600px;overflow:auto">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light"><tr>
                <th>Member</th><th>Mess</th><th class="text-end">Days</th><th class="text-end">Rate</th><th class="text-end">Amount</th>
              </tr></thead>
              <tbody>
                @forelse($memberRows as $r)
                  <tr>
                    <td>{{ $r['member'] }} <span class="text-muted small">{{ $r['member_code'] }}</span></td>
                    <td>{{ $r['mess'] }}</td>
                    <td class="text-end">{{ $r['present_days'] }}</td>
                    <td class="text-end">{{ $fmt($r['rate']) }}</td>
                    <td class="text-end">{{ $fmt($r['amount']) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted">No exec/centralized members.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
