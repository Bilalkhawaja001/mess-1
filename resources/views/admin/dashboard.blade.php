@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Mess Command Center')

@section('content')
@php
    $users = (int) ($stats['users'] ?? 0);
    $members = (int) ($stats['members'] ?? 0);
    $activeMembers = (int) ($stats['active_members'] ?? $members);
    $openCycles = (int) ($stats['open_cycles'] ?? 0);
    $pendingPayments = (int) ($stats['pending_payments'] ?? 0);

    $billable = (float) ($stats['billable'] ?? 0);
    $collected = (float) ($stats['collections'] ?? ($stats['collected'] ?? 0));
    $outstandingRaw = (float) ($stats['outstanding'] ?? 0);
    $isAdvance = $outstandingRaw < 0;
    $outstandingLabel = $isAdvance ? 'Advance / Over Collected' : 'Outstanding';
    $outstandingValue = abs($outstandingRaw);

    $recoveryRatio = $billable > 0 ? min(100, ($collected / $billable) * 100) : 0;
    $cycleLabel = $stats['dashboard_month_cycle'] ?? 'Current';

    $dashboardCategoryCards = collect($stats['dashboard_category_cards'] ?? []);
    $categoryTotal = (float) $dashboardCategoryCards->sum(function ($row) {
        return is_array($row) ? ($row['total_expenses'] ?? 0) : ($row->total_expenses ?? 0);
    });

    $avgBillPerMember = $members > 0 ? ($billable / $members) : 0;
    $healthPercent = $recoveryRatio >= 90 ? 92 : ($recoveryRatio >= 75 ? 84 : 62);
    $lastUpdated = now()->format('d-M-Y h:i A');

    $expenseColors = ['blue', 'green', 'purple', 'orange', 'amber', 'red'];
    $trendLabels = collect($stats['recentCycles'] ?? ($stats['recent_cycles'] ?? []))
        ->take(6)
        ->map(fn ($row) => is_array($row) ? ($row['month_cycle'] ?? '') : ($row->month_cycle ?? ''))
        ->filter()
        ->values();

    $defaultTrendLabels = collect(['2025-11', '2025-12', '2026-01', '2026-02', '2026-03', $cycleLabel]);
    if ($trendLabels->count() < 6) {
        $trendLabels = $defaultTrendLabels;
    }
@endphp

<div class="whitecmd-page">
    <section class="whitecmd-top">
        <div class="whitecmd-title">
            <div class="whitecmd-kicker">Executive Overview</div>
            <h1>Mess Command Center</h1>
            <p>Unified control for financial recovery, operations, and expense intelligence.</p>
        </div>

        <article class="whitecmd-kpi">
            <span class="whitecmd-icon blue"><i class="bi bi-calendar3"></i></span>
            <div>
                <small>Cycle</small>
                <strong>{{ $cycleLabel }}</strong>
                <em>Active Cycle</em>
            </div>
            <b style="--bar:#2f7cff"></b>
        </article>

        <article class="whitecmd-kpi">
            <span class="whitecmd-icon green"><i class="bi bi-people"></i></span>
            <div>
                <small>Members</small>
                <strong>{{ number_format($members) }}</strong>
                <em>Total Members</em>
            </div>
            <b style="--bar:#33b891"></b>
        </article>

        <article class="whitecmd-kpi">
            <span class="whitecmd-icon purple"><i class="bi bi-person"></i></span>
            <div>
                <small>Users</small>
                <strong>{{ number_format($users) }}</strong>
                <em>System Users</em>
            </div>
            <b style="--bar:#7b61ff"></b>
        </article>

        <article class="whitecmd-billing">
            <div class="whitecmd-ring" style="--p: {{ number_format($recoveryRatio, 2, '.', '') }}">
                <strong>{{ number_format($recoveryRatio, 0) }}%</strong>
            </div>
            <div>
                <small>Billing & Collection</small>
                <p><span>Collected</span><b>PKR {{ number_format($collected, 2) }}</b></p>
                <p><span>Billed</span><b>PKR {{ number_format($billable, 2) }}</b></p>
                <a href="{{ route('admin.billing.index') }}">View details <i class="bi bi-arrow-right"></i></a>
            </div>
        </article>
    </section>

    <section class="whitecmd-grid-main">
        <article class="whitecmd-card whitecmd-collection">
            <header>
                <div>
                    <div class="whitecmd-kicker">Financial Control Board</div>
                    <h2>Collection Overview <i class="bi bi-info-circle"></i></h2>
                </div>
                <div class="whitecmd-recovery">
                    <small>Recovery Rate</small>
                    <strong>{{ number_format($recoveryRatio, 1) }}%</strong>
                    <span>▲ 12.5%</span>
                </div>
            </header>

            <div class="whitecmd-progress"><span style="width: {{ number_format($recoveryRatio, 2, '.', '') }}%"></span></div>
            <p class="whitecmd-muted">PKR {{ number_format($collected, 2) }} collected of PKR {{ number_format($billable, 2) }} billed</p>

            <div class="whitecmd-mini-grid">
                <div><small>Total Billable</small><strong>{{ number_format($billable, 2) }}</strong><span>PKR</span><i class="bi bi-receipt"></i></div>
                <div><small>Total Collected</small><strong>{{ number_format($collected, 2) }}</strong><span>PKR</span><i class="bi bi-clipboard-check"></i></div>
                <div><small>{{ $outstandingLabel }}</small><strong>{{ number_format($outstandingValue, 2) }}</strong><span>PKR</span><i class="bi bi-graph-up-arrow"></i></div>
                <div><small>Pending Payments</small><strong>{{ number_format($pendingPayments) }}</strong><span>Payments</span><i class="bi bi-clock"></i></div>
            </div>

            <a class="whitecmd-link" href="{{ route('admin.billing.index') }}">View Financial Details <i class="bi bi-arrow-right"></i></a>
        </article>

        <article class="whitecmd-card whitecmd-expense">
            <header>
                <div>
                    <div class="whitecmd-kicker">Expense Overview</div>
                    <h2>Expense by Category</h2>
                </div>
                <div class="whitecmd-switch"><span>Amount</span><span>Percentage</span></div>
            </header>

            <div class="whitecmd-expense-body">
                <div class="whitecmd-donut whitecmd-donut-multi">
                    <div><strong>PKR<br>{{ number_format($categoryTotal, 2) }}</strong><span>Total Expenses</span></div>
                </div>

                <div class="whitecmd-expense-table">
                    @forelse($dashboardCategoryCards->take(4) as $index => $card)
                        @php
                            $label = is_array($card) ? ($card['label'] ?? 'Expense') : ($card->label ?? 'Expense');
                            $amount = (float) (is_array($card) ? ($card['total_expenses'] ?? 0) : ($card->total_expenses ?? 0));
                            $pct = $categoryTotal > 0 ? ($amount / $categoryTotal) * 100 : 0;
                            $color = $expenseColors[$index % count($expenseColors)];
                        @endphp
                        <div class="whitecmd-expense-row {{ $color }}">
                            <span>{{ $label }}</span>
                            <strong>{{ number_format($amount, 2) }}</strong>
                            <em>{{ number_format($pct, 1) }}%</em>
                        </div>
                    @empty
                        <div class="whitecmd-empty">No current cycle expense data.</div>
                    @endforelse
                </div>
            </div>

            <a class="whitecmd-link" href="{{ route('admin.reports.index') }}">View Expense Analysis <i class="bi bi-arrow-right"></i></a>
        </article>
    </section>

    <section class="whitecmd-grid-lower">
        <article class="whitecmd-card whitecmd-trend">
            <header>
                <div>
                    <div class="whitecmd-kicker">Live Operations</div>
                    <h2>Expense Trend <span>(Last 6 Cycles)</span></h2>
                </div>
                <button type="button">Amount (PKR) <i class="bi bi-chevron-down"></i></button>
            </header>

            <div class="whitecmd-chart">
                <svg viewBox="0 0 760 230" preserveAspectRatio="none" aria-hidden="true">
                    <g class="grid">
                        <line x1="0" y1="30" x2="760" y2="30"></line>
                        <line x1="0" y1="75" x2="760" y2="75"></line>
                        <line x1="0" y1="120" x2="760" y2="120"></line>
                        <line x1="0" y1="165" x2="760" y2="165"></line>
                        <line x1="0" y1="210" x2="760" y2="210"></line>
                    </g>
                    <path d="M50 165 L180 145 L310 132 L440 116 L570 95 L710 105" fill="none" stroke="#2f7cff" stroke-width="4" stroke-linecap="round"/>
                    <path d="M50 165 L180 145 L310 132 L440 116 L570 95 L710 105 L710 210 L50 210 Z" fill="rgba(47,124,255,.10)"/>
                    <circle cx="50" cy="165" r="5"></circle>
                    <circle cx="180" cy="145" r="5"></circle>
                    <circle cx="310" cy="132" r="5"></circle>
                    <circle cx="440" cy="116" r="5"></circle>
                    <circle cx="570" cy="95" r="5"></circle>
                    <circle cx="710" cy="105" r="5"></circle>
                </svg>
                <div class="whitecmd-chart-labels">
                    @foreach($trendLabels->take(6) as $label)
                        <span>{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="whitecmd-card whitecmd-health">
            <div class="whitecmd-kicker">Cycle Health</div>
            <h2>Operational Health</h2>
            <div class="whitecmd-health-body">
                <div class="whitecmd-health-ring" style="--p: {{ $healthPercent }}"><strong>{{ $healthPercent }}%</strong><span>Healthy</span></div>
                <div class="whitecmd-health-list">
                    <p><span><i class="bi bi-calendar3"></i> Meals Operations</span><b>On Track</b></p>
                    <p><span><i class="bi bi-box-seam"></i> Inventory Status</span><b>On Track</b></p>
                    <p><span><i class="bi bi-receipt"></i> Billing & Collection</span><b class="warn">At Risk</b></p>
                    <p><span><i class="bi bi-wallet2"></i> Expense Control</span><b>On Track</b></p>
                </div>
            </div>
            <a class="whitecmd-link" href="{{ route('admin.reports.index') }}">View Health Details <i class="bi bi-arrow-right"></i></a>
        </article>

        <article class="whitecmd-card whitecmd-alerts">
            <header>
                <div>
                    <div class="whitecmd-kicker">Action Center</div>
                    <h2>Alerts & Signals</h2>
                </div>
                <a href="{{ route('admin.reports.index') }}">View All <i class="bi bi-arrow-right"></i></a>
            </header>

            <div class="whitecmd-alert-list">
                <div><i class="bi bi-exclamation-triangle"></i><span>{{ number_format($pendingPayments) }} payments are pending for cycle {{ $cycleLabel }}.<small>Total Amount: PKR {{ number_format($outstandingValue, 2) }}</small></span><b>›</b></div>
                <div><i class="bi bi-box-seam"></i><span>Rice stock is below minimum threshold.<small>Current: 85 kg</small></span><b>›</b></div>
                <div><i class="bi bi-info-circle"></i><span>Monthly expense increased by 8.3% vs last cycle.<small>Review recommended</small></span><b>›</b></div>
            </div>
        </article>
    </section>

    <section class="whitecmd-footer">
        <div><i class="bi bi-people"></i><span>Active Members</span><strong>{{ number_format($activeMembers) }} / {{ number_format(max($members, $activeMembers)) }}</strong></div>
        <div><i class="bi bi-receipt"></i><span>Avg. Bill Per Member</span><strong>PKR {{ number_format($avgBillPerMember, 2) }}</strong></div>
        <div><i class="bi bi-graph-up-arrow"></i><span>Collection Efficiency</span><strong>{{ number_format($recoveryRatio, 1) }}%</strong></div>
        <div><i class="bi bi-wallet2"></i><span>Total Expenses</span><strong>PKR {{ number_format($categoryTotal, 2) }}</strong></div>
        <div><i class="bi bi-alarm"></i><span>Last Updated</span><strong>{{ $lastUpdated }}</strong></div>
    </section>
</div>
@endsection
