@extends('layouts.app')
@section('title', 'Payments')
@section('page_title', 'Payments')
@section('content')
@php
    $paymentRows   = $rows ?? collect();
    $billingMonths = $billingMonths ?? collect();
    $ageMap        = $ageMap ?? [];
    $proofMap      = $proofMap ?? [];
    $variance      = $varianceAmount ?? 0;
    $activeTab     = $activeTab ?? 'awaiting';
    $collected = ($postedBillAmount ?? 0) > 0
        ? min(100, round((($receivedPaymentAmount ?? 0) / $postedBillAmount) * 100))
        : 0;
    $ageDot = fn($t) => ['red' => '#B91C1C', 'amber' => '#B45309', 'green' => '#15803D'][$t] ?? '#78716C';
    $statusStyles = [
        'PENDING'                => ['bg'=>'#F5F5F4','color'=>'#57534E'],
        'APPROVED'               => ['bg'=>'#DCFCE7','color'=>'#15803D'],
        'RECONCILIATION_PENDING' => ['bg'=>'#FEF3C7','color'=>'#B45309'],
        'RECONCILED'             => ['bg'=>'#DBEAFE','color'=>'#1D4ED8'],
        'REVERSED'               => ['bg'=>'#FEE2E2','color'=>'#B91C1C'],
        'CANCELLED'              => ['bg'=>'#F5F5F4','color'=>'#57534E'],
        'FAILED'                 => ['bg'=>'#FEE2E2','color'=>'#B91C1C'],
    ];
    $pill = fn($s) => $statusStyles[$s] ?? ['bg'=>'#F5F5F4','color'=>'#57534E'];
@endphp

<style>
  #pmRoot{background:#FAFAF9;font-family:'Inter',system-ui,sans-serif;color:#1C1917;-webkit-font-smoothing:antialiased;min-height:calc(100vh - 120px)}
  #pmRoot *{box-sizing:border-box}
  #pmRoot a{color:#1E3A5F;text-decoration:none}
  #pmRoot a:hover{color:#16304d}
  @keyframes pmpulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.35;transform:scale(.72)}}
  #pmRoot .pm-row{cursor:pointer}
  #pmRoot .pm-row:hover{background:#FAFAF9 !important}
  #pmRoot .pm-tab:hover{color:#1E3A5F}
  #pmRoot.dense tbody td{padding:6px 16px !important;height:37px !important}
  #pmRoot .approve-mini{opacity:0;transition:opacity .1s}
  #pmRoot .pm-row:hover .approve-mini{opacity:1}
</style>

<div id="pmRoot">

  {{-- 1 · COMMAND BAR --}}
  <div style="background:#FFFFFF;border-bottom:1px solid #E7E5E4">
    <div style="height:56px;display:flex;align-items:center;justify-content:space-between;padding:0 24px">
      <div style="display:flex;align-items:center;gap:14px">
        <span style="font-size:15px;font-weight:600;letter-spacing:-.01em">Payments</span>
        <form method="GET" action="{{ route('admin.payments.index.v2') }}" style="margin:0">
          <input type="hidden" name="tab" value="{{ $activeTab }}">
          <select name="month_cycle" onchange="this.form.submit()"
                  style="height:28px;padding:0 8px;border:1px solid #E7E5E4;border-radius:6px;background:#FFFFFF;font-family:'IBM Plex Mono',monospace;font-size:12px;font-weight:500;color:#44403C;cursor:pointer">
            <option value="">All cycles</option>
            @foreach($billingMonths as $mc)
              <option value="{{ $mc }}" @selected($mc === $selectedMonthCycle)>{{ $mc }}</option>
            @endforeach
          </select>
        </form>
      </div>
      <button type="button" onclick="document.getElementById('pv2Modal').style.display='flex'"
              style="height:34px;padding:0 16px;background:#1E3A5F;color:#FFFFFF;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;letter-spacing:-.005em">Record Payment</button>
    </div>
    <div style="height:2px;background:#E7E5E4;width:100%"><div style="height:2px;background:#1E3A5F;width:{{ $collected }}%"></div></div>
  </div>

  {{-- FLASH --}}
  @if(session('status'))<div style="background:#DCFCE7;padding:9px 24px;font-size:13px;color:#15803D">{{ session('status') }}</div>@endif
  @if(session('success'))<div style="background:#DCFCE7;padding:9px 24px;font-size:13px;color:#15803D">{{ session('success') }}</div>@endif
  @if(session('error'))<div style="background:#FEE2E2;padding:9px 24px;font-size:13px;color:#B91C1C">{{ session('error') }}</div>@endif
  @if($errors->any())<div style="background:#FEE2E2;padding:9px 24px;font-size:13px;color:#B91C1C">{{ $errors->first() }}</div>@endif

  {{-- 2 · AWAITING BANNER --}}
  @if(($awaitingCount ?? 0) > 0)
  <a href="{{ route('admin.payments.index.v2', ['tab'=>'awaiting','month_cycle'=>$selectedMonthCycle]) }}"
     style="background:#FEF3C7;padding:9px 24px;font-size:12.5px;color:#92400E;display:flex;align-items:center;gap:8px;border-bottom:1px solid #FDE9AE">
    <span style="width:5px;height:5px;border-radius:50%;background:#B45309;display:inline-block"></span>
    <span><span style="font-weight:600">{{ $awaitingCount }} payments</span> need action · oldest {{ $oldestAwaitingDays }} days</span>
  </a>
  @endif

  {{-- 3 · POSITION STRIP --}}
  <div style="background:#FFFFFF;border-bottom:1px solid #E7E5E4">
    <div style="display:flex;align-items:stretch;padding:0 24px">
      <a href="{{ route('admin.payments.index.v2', ['tab'=>'all','month_cycle'=>$selectedMonthCycle]) }}"
         style="flex:1;min-width:0;padding:14px 24px 14px 0;border-bottom:2px solid {{ $activeTab==='all'?'#1E3A5F':'transparent' }}">
        <div style="font-size:11px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:#78716C">Billed</div>
        <div style="display:flex;align-items:baseline;gap:9px;margin-top:5px">
          <span style="font-family:'IBM Plex Mono',monospace;font-size:20px;font-weight:500;color:#1C1917;font-variant-numeric:tabular-nums">{{ number_format($postedBillAmount ?? 0, 2) }}</span>
          <span style="font-size:11.5px;color:#A8A29E">{{ $postedBillCount ?? 0 }} bills</span>
        </div>
      </a>
      <div style="width:1px;background:#E7E5E4;margin:12px 0"></div>
      <a href="{{ route('admin.payments.index.v2', ['tab'=>'recent','month_cycle'=>$selectedMonthCycle]) }}"
         style="flex:1;min-width:0;padding:14px 24px;border-bottom:2px solid {{ $activeTab==='recent'?'#1E3A5F':'transparent' }}">
        <div style="font-size:11px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:#78716C">Received</div>
        <div style="display:flex;align-items:baseline;gap:9px;margin-top:5px">
          <span style="font-family:'IBM Plex Mono',monospace;font-size:20px;font-weight:500;color:{{ ($receivedPaymentAmount ?? 0) >= ($postedBillAmount ?? 0) ? '#15803D' : '#1C1917' }};font-variant-numeric:tabular-nums">{{ number_format($receivedPaymentAmount ?? 0, 2) }}</span>
          <span style="font-size:11.5px;color:#A8A29E">{{ $receivedPaymentCount ?? 0 }} payments</span>
        </div>
      </a>
      <div style="width:1px;background:#E7E5E4;margin:12px 0"></div>
      <div style="flex:1;min-width:0;padding:14px 24px">
        <div style="font-size:11px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:#78716C">Variance</div>
        <div style="display:flex;align-items:baseline;gap:9px;margin-top:5px">
          <span style="font-family:'IBM Plex Mono',monospace;font-size:20px;font-weight:500;color:{{ $variance==0?'#1C1917':'#B45309' }};font-variant-numeric:tabular-nums">{{ $variance > 0 ? '+' : '' }}{{ number_format($variance,2) }}</span>
          @if($variance != 0)<span style="font-size:11.5px;color:#A8A29E">{{ $variance > 0 ? 'overpaid' : 'short' }}</span>@endif
        </div>
      </div>
      <div style="width:1px;background:#E7E5E4;margin:12px 0"></div>
      <a href="{{ route('admin.payments.index.v2', ['tab'=>'awaiting','month_cycle'=>$selectedMonthCycle]) }}"
         style="flex:1;min-width:0;padding:14px 0 14px 24px;border-bottom:2px solid {{ $activeTab==='awaiting'?'#1E3A5F':'transparent' }}">
        <div style="font-size:11px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:#78716C">Awaiting</div>
        <div style="display:flex;align-items:baseline;gap:9px;margin-top:5px">
          <span style="font-family:'IBM Plex Mono',monospace;font-size:20px;font-weight:500;color:{{ ($awaitingCount ?? 0) > 0 ? '#B91C1C' : '#1C1917' }};font-variant-numeric:tabular-nums">{{ $awaitingCount ?? 0 }} items</span>
          @if(($oldestAwaitingDays ?? 0) > 0)
          <span style="font-size:11.5px;color:#A8A29E;display:inline-flex;align-items:center;gap:6px"><span style="width:6px;height:6px;border-radius:50%;background:#B91C1C;display:inline-block;{{ ($oldestAwaitingDays ?? 0) > 14 ? 'animation:pmpulse 1.8s ease-in-out infinite' : '' }}"></span>oldest {{ $oldestAwaitingDays }} days</span>
          @endif
        </div>
      </a>
    </div>
  </div>

  {{-- 4 · TABS --}}
  <div style="background:#FAFAF9;border-bottom:1px solid #E7E5E4;padding:0 24px">
    <div style="display:flex;align-items:stretch;gap:2px;height:42px">
      @php
        $tabDefs = [
          'awaiting'       => ['Awaiting Action', $awaitingCount ?? 0],
          'recent'         => ['Recent', null],
          'reconciliation' => ['Reconciliation', $reconciliationCount ?? 0],
          'all'            => ['All', null],
        ];
      @endphp
      @foreach($tabDefs as $k => [$label, $cnt])
        @php $act = $activeTab === $k; $zero = ($cnt === 0); @endphp
        <a href="{{ route('admin.payments.index.v2', array_filter(['tab'=>$k,'month_cycle'=>$selectedMonthCycle])) }}"
           class="pm-tab"
           style="align-self:stretch;display:inline-flex;align-items:center;padding:0 14px;border-bottom:2px solid {{ $act?'#1E3A5F':'transparent' }};font-size:13px;font-weight:{{ $act?600:500 }};color:{{ $act?'#1E3A5F':'#57534E' }};opacity:{{ ($zero && !$act)?'0.4':'1' }}">{{ $label }}{{ $cnt !== null ? ' ('.$cnt.')' : '' }}</a>
      @endforeach
    </div>
  </div>

  {{-- 5 · FILTER STRIP --}}
  <form method="GET" action="{{ route('admin.payments.index.v2') }}" style="background:#FAFAF9;border-bottom:1px solid #E7E5E4;padding:12px 24px;margin:0">
    <input type="hidden" name="tab" value="{{ $activeTab }}">
    <input type="hidden" name="month_cycle" value="{{ $selectedMonthCycle }}">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <div style="position:relative;flex:1;min-width:260px;max-width:380px">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" style="position:absolute;left:11px;top:50%;transform:translateY(-50%)"><circle cx="7" cy="7" r="4.5" stroke="#A8A29E" stroke-width="1.4"/><path d="m11 11 3 3" stroke="#A8A29E" stroke-width="1.4" stroke-linecap="round"/></svg>
        <input name="ref" value="{{ request('ref') }}" placeholder="Member code, name, reference…" id="pmSearch"
               style="width:100%;height:34px;padding:0 12px 0 32px;border:1px solid #E7E5E4;border-radius:6px;background:#FFFFFF;font-size:13px;color:#1C1917;outline:none"/>
      </div>
      <select name="method" style="height:34px;padding:0 12px;border:1px solid #E7E5E4;border-radius:6px;background:#FFFFFF;font-size:13px;color:#44403C;cursor:pointer">
        <option value="">Method</option>
        @foreach(($methods ?? []) as $m)
          <option value="{{ $m->code }}" @selected(request('method') === $m->code)>{{ $m->name }}</option>
        @endforeach
      </select>
      <select name="status" style="height:34px;padding:0 12px;border:1px solid #E7E5E4;border-radius:6px;background:#FFFFFF;font-size:13px;color:#44403C;cursor:pointer">
        <option value="">Status</option>
        @foreach(['PENDING','APPROVED','RECONCILIATION_PENDING','RECONCILED','REVERSED','CANCELLED','FAILED'] as $st)
          <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_',' ',$st) }}</option>
        @endforeach
      </select>
      <button type="submit" style="height:34px;padding:0 16px;background:#1E3A5F;color:#FFFFFF;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer">Apply</button>
      @if(request()->hasAny(['ref','method','status']))
        <a href="{{ route('admin.payments.index.v2', ['tab'=>$activeTab]) }}" style="font-size:12.5px;color:#78716C;padding:0 4px">Clear all</a>
      @endif
    </div>
  </form>

  {{-- DENSITY BAR --}}
  <div style="padding:8px 24px 0">
    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:8px">
      <span style="font-size:12px;color:#A8A29E">{{ $paymentRows->count() }} shown</span>
      <div style="display:inline-flex;border:1px solid #E7E5E4;border-radius:6px;overflow:hidden;background:#FFFFFF">
        <button type="button" id="pmCompact" onclick="pmDensity(1)" style="padding:5px 12px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:#1E3A5F;color:#FFFFFF">Compact</button>
        <button type="button" id="pmComfort" onclick="pmDensity(0)" style="padding:5px 12px;font-size:12px;font-weight:500;border:none;cursor:pointer;background:#FFFFFF;color:#57534E">Comfortable</button>
      </div>
    </div>
  </div>

  {{-- 6 · LEDGER TABLE --}}
  <div style="padding:0 24px">
    <div style="border:1px solid #E7E5E4;border-radius:8px;background:#FFFFFF;overflow-x:auto">
      <table style="width:100%;min-width:1080px;border-collapse:collapse;table-layout:fixed">
        <thead>
          <tr>
            <th style="width:108px;text-align:left;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Date</th>
            <th style="width:170px;text-align:left;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Member</th>
            <th style="text-align:left;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Reference</th>
            <th style="width:120px;text-align:left;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Method</th>
            <th style="width:130px;text-align:right;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Amount (PKR)</th>
            <th style="width:172px;text-align:left;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Status</th>
            <th style="width:78px;text-align:left;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Age</th>
            <th style="width:64px;text-align:center;padding:9px 16px;background:#FAFAF9;border-bottom:1px solid #E7E5E4;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#78716C">Proof</th>
            <th style="width:90px;background:#FAFAF9;border-bottom:1px solid #E7E5E4"></th>
          </tr>
        </thead>
        <tbody>
        @forelse($paymentRows as $r)
          @php
            $a = $ageMap[$r->id] ?? ['days'=>0,'tone'=>'green'];
            $overdue = ($a['tone'] === 'red');
            $ps = $pill($r->status);
          @endphp
          <tr class="pm-row" data-pid="{{ $r->id }}" onclick="pmOpenInspector({{ $r->id }})"
              style="transition:background .1s;{{ $overdue ? 'box-shadow:inset 3px 0 0 #B91C1C;' : '' }}">
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle"><span style="font-family:'IBM Plex Mono',monospace;font-size:12.5px;color:#44403C;font-variant-numeric:tabular-nums;white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($r->payment_date)->format('d M Y') }}</span></td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle">
              <div style="font-family:'IBM Plex Mono',monospace;font-size:12.5px;font-weight:600;color:#1C1917;line-height:1.35">{{ $r->member->member_code ?? '—' }}</div>
              <div style="font-size:11px;color:#A8A29E;line-height:1.3;letter-spacing:.02em">{{ $r->member->name ?? '' }}</div>
            </td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle"><span style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#A8A29E;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">{{ $r->payment_ref ?: $r->reference_no ?: '—' }}</span></td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle"><span style="font-size:12.5px;color:#44403C">{{ $r->methodRecord->name ?? $r->method }}</span></td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle;text-align:right"><span style="font-family:'IBM Plex Mono',monospace;font-size:13px;color:#1C1917;font-variant-numeric:tabular-nums">{{ number_format($r->amount, 2) }}</span></td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle"><span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;letter-spacing:.01em;white-space:nowrap;background:{{ $ps['bg'] }};color:{{ $ps['color'] }}">{{ str_replace('_',' ',$r->status) }}</span></td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle"><span style="display:inline-flex;align-items:center;gap:6px;font-family:'IBM Plex Mono',monospace;font-size:12px;color:#57534E;font-variant-numeric:tabular-nums"><span style="width:7px;height:7px;border-radius:50%;background:{{ $ageDot($a['tone']) }};display:inline-block;flex:none"></span>{{ $a['days'] }}d</span></td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle;text-align:center">
              @if(isset($proofMap[$r->id]))
                <a href="{{ $proofMap[$r->id]['url'] }}" target="_blank" onclick="event.stopPropagation()" title="Open proof"><span style="display:inline-block;width:26px;height:26px;border-radius:4px;border:1px solid #E7E5E4;background-image:url('{{ $proofMap[$r->id]['url'] }}');background-size:cover;background-position:center"></span></a>
              @else
                <span style="color:#D6D3D1;font-family:'IBM Plex Mono',monospace">—</span>
              @endif
            </td>
            <td style="padding:9px 16px;border-bottom:1px solid #F0EEEC;vertical-align:middle;text-align:right" onclick="event.stopPropagation()">
              @if(in_array($r->status, ['PENDING','RECONCILIATION_PENDING']))
                <form method="POST" action="{{ route('admin.payments.approve', $r) }}" style="display:inline" class="approve-mini"
                      onsubmit="return confirm('Approve payment of {{ number_format($r->amount,2) }} for {{ $r->member->member_code ?? '' }}?')">
                  @csrf
                  <button type="submit" style="border:none;background:none;color:#15803D;font-weight:600;font-size:12px;cursor:pointer">Approve</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="9" style="padding:56px 24px;text-align:center;color:#78716C;font-size:13.5px">Queue clear. Nothing waiting.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    {{-- totals --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;margin-top:-1px;border:1px solid #E7E5E4;border-top:none;border-radius:0 0 8px 8px;background:#FFFFFF">
      <span style="font-size:12px;color:#78716C">{{ $paymentRows->count() }} shown</span>
      <span style="font-size:12px;color:#57534E;display:inline-flex;align-items:baseline;gap:10px">Filtered total <span style="font-family:'IBM Plex Mono',monospace;font-size:13.5px;font-weight:600;color:#1C1917;font-variant-numeric:tabular-nums">PKR {{ number_format($filteredTotal ?? 0, 2) }}</span></span>
    </div>
  </div>

</div>

{{-- INSPECTOR PANEL --}}
<div id="pv2Inspector" style="display:none;position:fixed;top:150px;right:0;height:calc(100vh - 150px);width:480px;max-width:96vw;background:#fff;border-left:1px solid #E7E5E4;box-shadow:-8px 0 24px rgba(0,0,0,.06);z-index:1040;flex-direction:column;font-family:'Inter',sans-serif">
  <div style="padding:18px 22px;border-bottom:1px solid #E7E5E4;display:flex;justify-content:space-between;align-items:flex-start">
    <div>
      <div id="insMemberCode" style="font-weight:600;font-size:14px;font-family:'IBM Plex Mono',monospace">—</div>
      <div id="insMemberName" style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#78716C;margin-top:3px">—</div>
      <div style="font-size:11px;color:#A8A29E;margin-top:8px">J / K move · Esc close</div>
    </div>
    <div style="text-align:right">
      <button type="button" onclick="pmCloseInspector()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#78716C;line-height:1">&times;</button>
      <div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#78716C;margin-top:12px">Remaining</div>
      <div id="insRemaining" style="font-size:15px;margin-top:2px;font-family:'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums;color:#B45309">—</div>
    </div>
  </div>
  <div style="flex:1;overflow:auto;padding:22px;display:flex;flex-direction:column;gap:22px">
    <dl style="display:grid;grid-template-columns:1fr 1fr;gap:14px 18px;margin:0">
      <div><dt style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#78716C;margin-bottom:3px">Date</dt><dd id="insDate" style="margin:0;font-size:13px">—</dd></div>
      <div><dt style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#78716C;margin-bottom:3px">Amount (PKR)</dt><dd id="insAmount" style="margin:0;font-size:13px;font-family:'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums">—</dd></div>
      <div><dt style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#78716C;margin-bottom:3px">Method</dt><dd id="insMethod" style="margin:0;font-size:13px">—</dd></div>
      <div><dt style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#78716C;margin-bottom:3px">Status</dt><dd id="insStatus" style="margin:0;font-size:13px">—</dd></div>
      <div style="grid-column:1/-1"><dt style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#78716C;margin-bottom:3px">Reference</dt><dd id="insRef" style="margin:0;font-size:12px;font-family:'IBM Plex Mono',monospace;word-break:break-all">—</dd></div>
    </dl>
    <div>
      <div style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#78716C;margin-bottom:10px">Linked Bill <span id="insBillId"></span></div>
      <div style="display:flex;border:1px solid #E7E5E4;border-radius:5px">
        <div style="flex:1;padding:10px;text-align:center;border-right:1px solid #E7E5E4"><div style="font-size:10px;text-transform:uppercase;color:#78716C">Billed</div><div id="insBilled" style="font-size:14px;margin-top:3px;font-family:'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums">—</div></div>
        <div style="flex:1;padding:10px;text-align:center;border-right:1px solid #E7E5E4;background:#FAFAF9"><div style="font-size:10px;text-transform:uppercase;color:#78716C">Paid</div><div id="insPaid" style="font-size:14px;margin-top:3px;color:#15803D;font-family:'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums">—</div></div>
        <div style="flex:1;padding:10px;text-align:center"><div style="font-size:10px;text-transform:uppercase;color:#78716C">Remaining</div><div id="insBillRemaining" style="font-size:14px;margin-top:3px;color:#B45309;font-family:'IBM Plex Mono',monospace;font-variant-numeric:tabular-nums">—</div></div>
      </div>
    </div>
    <div>
      <div style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#78716C;margin-bottom:14px">Status Timeline</div>
      <ul id="insTimeline" style="list-style:none;margin:0;padding:0;border-left:1px solid #E7E5E4"></ul>
    </div>
  </div>
</div>

{{-- RECORD PAYMENT MODAL --}}
<div id="pv2Modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1050;align-items:center;justify-content:center">
  <div style="background:#fff;width:520px;max-width:94vw;border-radius:8px;overflow:hidden;font-family:'Inter',sans-serif">
    <div style="padding:16px 22px;border-bottom:1px solid #E7E5E4;display:flex;justify-content:space-between;align-items:center">
      <strong style="font-size:15px">Record Payment</strong>
      <button type="button" onclick="document.getElementById('pv2Modal').style.display='none'" style="border:0;background:none;font-size:22px;cursor:pointer;color:#78716C">&times;</button>
    </div>
    <form method="POST" action="{{ route('admin.payments.store') }}" style="padding:22px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
      @csrf
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:5px">Member <span style="color:#B91C1C">*</span></label>
        <input id="pv2Member" name="member_lookup" placeholder="Member code, ID or name" autocomplete="off" required style="width:100%;height:36px;padding:0 10px;border:1px solid #D6D3D1;border-radius:5px;font-size:13px">
        <input type="hidden" id="pv2MemberId" name="member_id" required>
        <div id="pv2Status" style="font-size:11px;color:#78716C;margin-top:5px">Enter member code, numeric ID, or name</div>
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:5px">Bill ID <span style="color:#B91C1C">*</span></label>
        <input id="pv2Bill" name="bill_id" type="number" min="1" required readonly style="width:100%;height:36px;padding:0 10px;border:1px solid #D6D3D1;border-radius:5px;font-size:13px;background:#F5F5F4">
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:5px">Method <span style="color:#B91C1C">*</span></label>
        <select name="payment_method_id" required style="width:100%;height:36px;border:1px solid #D6D3D1;border-radius:5px;font-size:13px">
          <option value="">Select</option>
          @foreach(($methods ?? []) as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
        </select>
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:5px">Payment Date</label>
        <input type="date" name="payment_date" value="{{ now()->toDateString() }}" style="width:100%;height:36px;padding:0 10px;border:1px solid #D6D3D1;border-radius:5px;font-size:13px">
      </div>
      <div>
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:5px">Amount (PKR) <span style="color:#B91C1C">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" required style="width:100%;height:36px;padding:0 10px;border:1px solid #D6D3D1;border-radius:5px;font-size:13px">
      </div>
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:11px;text-transform:uppercase;color:#78716C;margin-bottom:5px">Reference No.</label>
        <input name="reference_no" placeholder="Bank / transaction reference" autocomplete="off" style="width:100%;height:36px;padding:0 10px;border:1px solid #D6D3D1;border-radius:5px;font-size:13px">
      </div>
      <input type="hidden" name="idempotency_key" value="MANPAY-{{ now()->format('YmdHis') }}-{{ str_pad((string) random_int(1,9999), 4, '0', STR_PAD_LEFT) }}">
      <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px;padding-top:8px;border-top:1px solid #E7E5E4">
        <button type="button" onclick="document.getElementById('pv2Modal').style.display='none'" style="height:36px;padding:0 16px;background:#fff;border:1px solid #D6D3D1;border-radius:5px;font-size:13px;cursor:pointer">Cancel</button>
        <button type="submit" style="height:36px;padding:0 18px;background:#1E3A5F;color:#fff;border:0;border-radius:5px;font-size:13px;font-weight:600;cursor:pointer">Create Attempt</button>
      </div>
    </form>
  </div>
</div>

<script>
function pmDensity(compact){
  var w=document.getElementById('pmRoot');if(!w)return;
  w.classList.toggle('dense',!!compact);
  document.getElementById('pmCompact').style.background=compact?'#1E3A5F':'#FFFFFF';
  document.getElementById('pmCompact').style.color=compact?'#FFFFFF':'#57534E';
  document.getElementById('pmComfort').style.background=compact?'#FFFFFF':'#1E3A5F';
  document.getElementById('pmComfort').style.color=compact?'#57534E':'#FFFFFF';
}
function pmCloseInspector(){var p=document.getElementById('pv2Inspector');if(p)p.style.display='none';window.pmPid=null;}
function pmOpenInspector(pid){
  var p=document.getElementById('pv2Inspector');if(!p)return;
  window.pmPid=pid;p.style.display='flex';
  var base="{{ url('/admin/payments') }}";
  fetch(base+"/"+pid+"/detail",{headers:{"Accept":"application/json"}})
    .then(function(r){return r.json()})
    .then(function(j){
      if(!j.ok)return;
      var $=function(id){return document.getElementById(id)};
      $("insMemberCode").textContent=j.member.code||"—";
      $("insMemberName").textContent=j.member.name||"";
      $("insDate").textContent=j.payment.date||"—";
      $("insAmount").textContent=j.payment.amount||"—";
      $("insMethod").textContent=j.payment.method||"—";
      $("insStatus").textContent=j.payment.status||"—";
      $("insRef").textContent=j.payment.reference||"—";
      $("insBillId").textContent=j.bill.id?("#"+j.bill.id):"";
      $("insBilled").textContent=j.bill.billed||"—";
      $("insPaid").textContent=j.bill.paid||"—";
      $("insBillRemaining").textContent=j.bill.remaining||"—";
      $("insRemaining").textContent=j.bill.remaining||"—";
      var tl=$("insTimeline");tl.innerHTML="";
      if(!j.timeline||!j.timeline.length){tl.innerHTML='<li style="padding-left:16px;color:#A8A29E;font-size:12px">No history recorded.</li>';}
      j.timeline.forEach(function(e){
        var li=document.createElement("li");li.style.cssText="position:relative;padding-left:18px;margin-bottom:16px";
        li.innerHTML='<span style="position:absolute;left:-4px;top:5px;width:7px;height:7px;border-radius:50%;background:#E7E5E4;border:1px solid #c4c6cf"></span>'+
          '<div style="font-size:13px;font-weight:500;text-transform:capitalize">'+(e.action||"")+'</div>'+
          '<div style="font-size:11px;color:#78716C;margin-top:2px">'+(e.at||"")+' · '+(e.actor||"")+'</div>'+
          (e.reason?'<div style="font-size:12px;color:#57534E;margin-top:4px">'+e.reason+'</div>':'');
        tl.appendChild(li);
      });
    }).catch(function(){});
}
function pmMoveRow(dir){
  var rows=Array.prototype.slice.call(document.querySelectorAll("tr[data-pid]"));
  if(!rows.length)return;
  var idx=rows.findIndex(function(r){return String(r.getAttribute("data-pid"))===String(window.pmPid)});
  idx=idx+dir;if(idx<0)idx=0;if(idx>=rows.length)idx=rows.length-1;
  var next=rows[idx];if(next){pmOpenInspector(next.getAttribute("data-pid"));next.scrollIntoView({block:"nearest"});}
}
document.addEventListener("keydown",function(e){
  if(e.key==="/" && e.target.tagName!=="INPUT" && e.target.tagName!=="SELECT" && e.target.tagName!=="TEXTAREA"){
    var s=document.getElementById("pmSearch");if(s){e.preventDefault();s.focus();}return;
  }
  var ins=document.getElementById("pv2Inspector");
  if(!ins||ins.style.display==="none")return;
  if(e.target.tagName==="INPUT"||e.target.tagName==="SELECT"||e.target.tagName==="TEXTAREA")return;
  if(e.key==="Escape")pmCloseInspector();
  else if(e.key==="j"||e.key==="J")pmMoveRow(1);
  else if(e.key==="k"||e.key==="K")pmMoveRow(-1);
});

/* MEMBER LOOKUP */
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
