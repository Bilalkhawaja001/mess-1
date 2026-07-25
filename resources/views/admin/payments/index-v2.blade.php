@extends('layouts.app')
@section('title', 'Payments')
@section('page_title', 'Payments')
@section('content')
@php
    $paymentRows = $rows ?? collect();
    $billingMonths = $billingMonths ?? collect();
    $ageMap = $ageMap ?? [];
    $proofMap = $proofMap ?? [];
    $variance = $varianceAmount ?? 0;
    $collected = ($postedBillAmount ?? 0) > 0
        ? min(100, round((($receivedPaymentAmount ?? 0) / $postedBillAmount) * 100))
        : 0;
    $tone = fn($t) => ['red' => '#dc2626', 'amber' => '#d97706', 'green' => '#16a34a'][$t] ?? '#9ca3af';
    $statusPill = function ($s) {
        return [
            'APPROVED'               => 'background:#DCFCE7;color:#15803D',
            'RECONCILED'             => 'background:#DBEAFE;color:#1D4ED8',
            'RECONCILIATION_PENDING' => 'background:#FEF3C7;color:#B45309',
            'PENDING'                => 'background:#F5F5F4;color:#57534E',
            'CANCELLED'              => 'background:#F5F5F4;color:#57534E',
            'REVERSED'               => 'background:#FEE2E2;color:#B91C1C',
            'FAILED'                 => 'background:#FEE2E2;color:#B91C1C',
        ][$s] ?? 'background:#F5F5F4;color:#57534E';
    };
@endphp

<style>
.pv2{--line:#E7E5E4;--navy:#1E3A5F}
.pv2 .strip{display:flex;background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.pv2 .fig{flex:1;padding:14px 24px;border-right:1px solid var(--line);cursor:pointer;text-decoration:none;color:inherit;display:block}
.pv2 .fig:last-child{border-right:0}
.pv2 .fig.active{box-shadow:inset 0 -2px 0 var(--navy)}
.pv2 .fig .lbl{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#78716C;margin-bottom:4px}
.pv2 .fig .val{font-family:'IBM Plex Mono',monospace;font-size:20px;font-variant-numeric:tabular-nums}
.pv2 .fig .sub{font-size:11px;color:#78716C;margin-left:8px}
.pv2 .tabs{display:flex;gap:24px;padding:0 24px;background:#fff;border-bottom:1px solid var(--line)}
.pv2 .tabs a{padding:12px 0;font-size:13px;color:#57534E;text-decoration:none;border-bottom:2px solid transparent}
.pv2 .tabs a.on{color:var(--navy);font-weight:600;border-bottom-color:var(--navy)}
.pv2 .filters{display:flex;gap:8px;align-items:center;padding:8px 24px;background:#FAFAF9;border-bottom:1px solid var(--line);flex-wrap:wrap}
.pv2 .filters input,.pv2 .filters select{height:32px;padding:0 8px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px;background:#fff}
.pv2 table{width:100%;border-collapse:collapse;background:#fff}
.pv2 thead th{position:sticky;top:0;background:#fff;z-index:5;padding:10px 8px;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#74777f;text-align:left;border-bottom:1px solid var(--line);white-space:nowrap}
.pv2 tbody td{padding:8px;border-bottom:1px solid var(--line);font-size:13px;vertical-align:middle}
.pv2 tbody tr:hover{background:#F5F5F4}
.pv2 .mono{font-family:'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums}
.pv2 .pill{display:inline-block;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:700;text-transform:uppercase;white-space:nowrap}
.pv2 .dot{display:inline-block;width:6px;height:6px;border-radius:50%;margin-right:6px}
.pv2 .stale{border-left:3px solid #dc2626}
.pv2 .totals{display:flex;justify-content:space-between;padding:10px 24px;background:#F5F5F4;border-top:1px solid var(--line);font-size:12px;color:#57534E}
.pv2 .empty{padding:40px;text-align:center;color:#78716C;font-size:13px}
</style>

<div class="pv2">

  <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 24px;background:#fff;border-bottom:1px solid var(--line)">
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#78716C">Payments Operations</div>
    <button type="button" onclick="document.getElementById('pv2Modal').style.display='flex'"
            style="height:32px;padding:0 14px;background:#1E3A5F;color:#fff;border:0;border-radius:3px;font-size:13px;font-weight:600;cursor:pointer">Record Payment</button>
  </div>

  @if(session('status'))<div style="padding:10px 24px;background:#DCFCE7;color:#15803D;font-size:13px">{{ session('status') }}</div>@endif
  @if(session('error'))<div style="padding:10px 24px;background:#FEE2E2;color:#B91C1C;font-size:13px">{{ session('error') }}</div>@endif
  @if($errors->any())<div style="padding:10px 24px;background:#FEE2E2;color:#B91C1C;font-size:13px">{{ $errors->first() }}</div>@endif

  @if(($awaitingCount ?? 0) > 0)
  <a href="{{ route('admin.payments.index.v2', ['tab'=>'awaiting','month_cycle'=>$selectedMonthCycle]) }}"
     style="display:block;padding:10px 24px;background:#FEF3C7;border-bottom:1px solid #FDE68A;color:#92400E;font-size:13px;font-weight:600;text-decoration:none">
    {{ $awaitingCount }} payments need action · oldest {{ $oldestAwaitingDays }} days
  </a>
  @endif

  <div style="height:2px;background:#E7E5E4">
    <div style="height:100%;width:{{ $collected }}%;background:#1E3A5F"></div>
  </div>

  <div class="strip">
    <a class="fig" href="{{ route('admin.payments.index.v2', ['tab'=>'all','month_cycle'=>$selectedMonthCycle]) }}">
      <div class="lbl">Billed</div>
      <div><span class="val">{{ number_format($postedBillAmount ?? 0, 2) }}</span><span class="sub">{{ $postedBillCount ?? 0 }} bills</span></div>
    </a>
    <a class="fig" href="{{ route('admin.payments.index.v2', ['tab'=>'recent','month_cycle'=>$selectedMonthCycle]) }}">
      <div class="lbl">Received</div>
      <div><span class="val" style="color:#16a34a">{{ number_format($receivedPaymentAmount ?? 0, 2) }}</span><span class="sub">{{ $receivedPaymentCount ?? 0 }} payments</span></div>
    </a>
    <div class="fig" style="cursor:default">
      <div class="lbl">Variance</div>
      <div>
        <span class="val" style="color:{{ $variance == 0 ? '#57534E' : '#B45309' }}">{{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }}</span>
        @if($variance != 0)
          <span class="sub" style="color:#B45309">{{ $variance > 0 ? 'overpaid' : 'underpaid' }}</span>
        @endif
      </div>
    </div>
    <a class="fig {{ ($activeTab ?? '') === 'awaiting' ? 'active' : '' }}" href="{{ route('admin.payments.index.v2', ['tab'=>'awaiting','month_cycle'=>$selectedMonthCycle]) }}">
      <div class="lbl">Awaiting</div>
      <div>
        <span class="val" style="color:{{ ($awaitingCount ?? 0) > 0 ? '#B91C1C' : '#57534E' }}">{{ $awaitingCount ?? 0 }} items</span>
        @if(($oldestAwaitingDays ?? 0) > 0)<span class="sub" style="color:#B91C1C">oldest {{ $oldestAwaitingDays }} days</span>@endif
      </div>
    </a>
  </div>

  <div class="tabs">
    @foreach(['awaiting'=>'Awaiting Action ('.($awaitingCount ?? 0).')','recent'=>'Recent','reconciliation'=>'Reconciliation ('.($reconciliationCount ?? 0).')','all'=>'All'] as $k => $label)
      <a class="{{ ($activeTab ?? 'awaiting') === $k ? 'on' : '' }}"
         href="{{ route('admin.payments.index.v2', array_filter(['tab'=>$k,'month_cycle'=>$selectedMonthCycle])) }}">{{ $label }}</a>
    @endforeach
  </div>

  <form method="GET" action="{{ route('admin.payments.index.v2') }}" class="filters">
    <input type="hidden" name="tab" value="{{ $activeTab ?? 'awaiting' }}">
    <select name="month_cycle" onchange="this.form.submit()">
      <option value="">All cycles</option>
      @foreach($billingMonths as $mc)
        <option value="{{ $mc }}" @selected($mc === $selectedMonthCycle)>{{ $mc }}</option>
      @endforeach
    </select>
    <input type="text" name="ref" value="{{ request('ref') }}" placeholder="Member code, name, reference…" style="min-width:260px">
    <select name="method">
      <option value="">Method</option>
      @foreach(($methods ?? []) as $m)
        <option value="{{ $m->code }}" @selected(request('method') === $m->code)>{{ $m->name }}</option>
      @endforeach
    </select>
    <select name="status">
      <option value="">Status</option>
      @foreach(['PENDING','APPROVED','RECONCILIATION_PENDING','RECONCILED','REVERSED','CANCELLED','FAILED'] as $st)
        <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_',' ',$st) }}</option>
      @endforeach
    </select>
    <button type="submit" style="height:32px;padding:0 14px;background:#1E3A5F;color:#fff;border:0;border-radius:3px;font-size:13px;font-weight:600">Apply</button>
    @if(request()->hasAny(['ref','method','status']))
      <a href="{{ route('admin.payments.index.v2', ['tab'=>$activeTab ?? 'awaiting']) }}" style="font-size:13px;color:#1E3A5F">Clear all</a>
    @endif
  </form>

  <div style="max-height:calc(100vh - 380px);overflow:auto">
    <table>
      <thead>
        <tr>
          <th>Date</th><th>Member</th><th>Reference</th><th>Method</th>
          <th style="text-align:right">Amount (PKR)</th><th>Status</th><th>Age</th><th>Proof</th><th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($paymentRows as $r)
        @php $a = $ageMap[$r->id] ?? ['days'=>0,'tone'=>'green']; @endphp
        <tr class="{{ $a['tone'] === 'red' ? 'stale' : '' }}">
          <td style="white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($r->payment_date)->format('d-M-Y') }}</td>
          <td>
            <div style="font-weight:600">{{ $r->member->member_code ?? '—' }}</div>
            <div style="font-size:11px;color:#78716C">{{ $r->member->name ?? '' }}</div>
          </td>
          <td class="mono" style="font-size:11px;color:#57534E">{{ $r->payment_ref ?: $r->reference_no ?: '—' }}</td>
          <td>{{ $r->methodRecord->name ?? $r->method }}</td>
          <td class="mono" style="text-align:right">{{ number_format($r->amount, 2) }}</td>
          <td><span class="pill" style="{{ $statusPill($r->status) }}">{{ str_replace('_',' ',$r->status) }}</span></td>
          <td style="white-space:nowrap"><span class="dot" style="background:{{ $tone($a['tone']) }}"></span>{{ $a['days'] }}d</td>
          <td>
            @if(isset($proofMap[$r->id]))
              <a href="{{ $proofMap[$r->id]['url'] }}" target="_blank" style="font-size:11px;color:#1E3A5F">View</a>
            @else
              <span style="color:#a8a29e">—</span>
            @endif
          </td>
          <td style="text-align:right;white-space:nowrap">
            @if(in_array($r->status, ['PENDING','RECONCILIATION_PENDING']))
              <form method="POST" action="{{ route('admin.payments.approve', $r) }}" style="display:inline"
                    onsubmit="return confirm('Approve payment of {{ number_format($r->amount,2) }} for {{ $r->member->member_code ?? '' }}?')">
                @csrf
                <button type="submit" style="border:0;background:none;color:#15803D;font-weight:600;font-size:12px;cursor:pointer">Approve</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="empty">Queue clear — nothing waiting.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="totals">
    <span>{{ $paymentRows->count() }} shown</span>
    <span class="mono"><strong>PKR {{ number_format($filteredTotal ?? 0, 2) }}</strong></span>
  </div>

</div>

<div id="pv2Modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1050;align-items:center;justify-content:center">
  <div style="background:#fff;width:520px;max-width:94vw;border-radius:6px;overflow:hidden">
    <div style="padding:14px 20px;border-bottom:1px solid #E7E5E4;display:flex;justify-content:space-between;align-items:center">
      <strong style="font-size:15px">Record Payment</strong>
      <button type="button" onclick="document.getElementById('pv2Modal').style.display='none'"
              style="border:0;background:none;font-size:20px;cursor:pointer;color:#78716C">&times;</button>
    </div>
    <form method="POST" action="{{ route('admin.payments.store') }}" style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:12px">
      @csrf
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:4px">Member <span style="color:#B91C1C">*</span></label>
        <input id="pv2Member" name="member_lookup" placeholder="Member code, ID or name" autocomplete="off" required
               style="width:100%;height:34px;padding:0 8px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px">
        <input type="hidden" id="pv2MemberId" name="member_id" required>
        <div id="pv2Status" style="font-size:11px;color:#78716C;margin-top:4px">Enter member code, numeric ID, or name</div>
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:4px">Bill ID <span style="color:#B91C1C">*</span></label>
        <input id="pv2Bill" name="bill_id" type="number" min="1" required readonly
               style="width:100%;height:34px;padding:0 8px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px;background:#F5F5F4">
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:4px">Method <span style="color:#B91C1C">*</span></label>
        <select name="payment_method_id" required style="width:100%;height:34px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px">
          <option value="">Select</option>
          @foreach(($methods ?? []) as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
        </select>
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:4px">Payment Date</label>
        <input type="date" name="payment_date" value="{{ now()->toDateString() }}"
               style="width:100%;height:34px;padding:0 8px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px">
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:4px">Amount (PKR) <span style="color:#B91C1C">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" required
               style="width:100%;height:34px;padding:0 8px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px">
      </div>
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:4px">Reference No.</label>
        <input name="reference_no" placeholder="Bank / transaction reference" autocomplete="off"
               style="width:100%;height:34px;padding:0 8px;border:1px solid #c4c6cf;border-radius:3px;font-size:13px">
      </div>
      <input type="hidden" name="idempotency_key" value="MANPAY-{{ now()->format('YmdHis') }}-{{ str_pad((string) random_int(1,9999), 4, '0', STR_PAD_LEFT) }}">
      <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px;padding-top:6px;border-top:1px solid #E7E5E4">
        <button type="button" onclick="document.getElementById('pv2Modal').style.display='none'"
                style="height:34px;padding:0 14px;background:#fff;border:1px solid #c4c6cf;border-radius:3px;font-size:13px;cursor:pointer">Cancel</button>
        <button type="submit" style="height:34px;padding:0 16px;background:#1E3A5F;color:#fff;border:0;border-radius:3px;font-size:13px;font-weight:600;cursor:pointer">Create Attempt</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  var inp=document.getElementById('pv2Member'), mid=document.getElementById('pv2MemberId'),
      bill=document.getElementById('pv2Bill'), st=document.getElementById('pv2Status'), t=null;
  if(!inp) return;
  inp.addEventListener('input', function(){
    clearTimeout(t);
    mid.value=''; bill.value=''; st.textContent='Searching…'; st.style.color='#78716C';
    var q=inp.value.trim();
    if(q.length<2){ st.textContent='Enter member code, numeric ID, or name'; return; }
    t=setTimeout(function(){
      fetch('{{ route('admin.payments.member-bill-lookup') }}?q='+encodeURIComponent(q), {headers:{'Accept':'application/json'}})
        .then(function(r){return r.json()})
        .then(function(j){
          var m=j.member||j.data||j;
          if(m && (m.member_id||m.id)){
            mid.value=m.member_id||m.id;
            if(j.bill_id||m.bill_id){ bill.value=j.bill_id||m.bill_id; }
            var os=(j.outstanding!=null)?Number(j.outstanding).toLocaleString('en-PK',{minimumFractionDigits:2,maximumFractionDigits:2}):null;
            st.textContent=(m.name||'')+' · bill '+(bill.value||'not found')+(os!=null?' · outstanding '+os:'');
            st.style.color=bill.value?'#15803D':'#B45309';
          } else {
            st.textContent='No match'; st.style.color='#B91C1C';
          }
        })
        .catch(function(){ st.textContent='Lookup failed'; st.style.color='#B91C1C'; });
    }, 350);
  });
})();
</script>
@endsection
