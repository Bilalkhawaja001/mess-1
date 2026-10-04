@php
$nav = [
 ['dashboard','Dashboard','dashboard',url('/admin/ui-preview/dashboard')],
 ['members','Members','groups',url('/admin/ui-preview/members')],
 ['attendance','Attendance & Meals','fact_check','#'],
 ['guests','Guest Meals','room_service','#'],
 ['billing','Billing','receipt_long','#'],
 ['payments','Payments','payments','#'],
 ['store','Purchase & Store','inventory_2','#'],
 ['approvals','Approvals','verified','#'],
 ['reports','Reports','bar_chart','#'],
 ['administration','Administration','admin_panel_settings','#'],
 ['audit','Audit Logs','manage_search','#'],
];
@endphp
<aside class="mess-ui-sidebar"><div class="mess-ui-brand"><div class="mess-ui-logo">H</div><div><div class="mess-ui-brand-title">Humen</div><div class="mess-ui-brand-sub">Mess Billing</div></div></div><div class="mess-ui-nav-label">Operations</div>@foreach($nav as $item)<a class="mess-ui-link mess-ui-nav-item {{ ($active ?? '') === $item[0] ? 'is-active' : '' }}" href="{{ $item[3] }}"><span class="mess-ui-icon">{{ $item[2] }}</span><span>{{ $item[1] }}</span></a>@endforeach</aside>