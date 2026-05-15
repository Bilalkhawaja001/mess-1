@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 fleet-command-center">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Fleet Command Center</h1>
            <small class="text-muted">Fuel, maintenance, documents, availability and cost control.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach(($quickActions ?? []) as $action)
                @if(Route::has($action['route']))
                    <a href="{{ route($action['route']) }}" class="btn btn-sm btn-outline-primary">{{ $action['label'] }}</a>
                @endif
            @endforeach
            @foreach(($reportActions ?? []) as $action)
                @if(Route::has($action['route']))
                    <a href="{{ route($action['route']) }}" class="btn btn-sm btn-dark">{{ $action['label'] }}</a>
                @endif
            @endforeach
        </div>
    </div>

    <div class="row g-3 mb-3">
        @forelse(($kpis ?? []) as $label => $value)
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase">{{ str_replace('_', ' ', $label) }}</small>
                        <h2 class="mb-0">{{ is_numeric($value) ? number_format($value, 2) : $value }}</h2>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light border mb-0">No KPI data available yet.</div></div>
        @endforelse
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold d-flex justify-content-between"><span>Fuel Trend</span><small class="text-muted">Server data</small></div>
                <div class="card-body">
                    @if(!empty($fuelTrend))
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Month</th><th class="text-end">Liters</th><th class="text-end">Distance</th><th class="text-end">Fuel Cost</th></tr></thead>
                                <tbody>
                                    @foreach($fuelTrend as $row)
                                        <tr><td>{{ $row['month'] }}</td><td class="text-end">{{ number_format($row['liters'], 2) }}</td><td class="text-end">{{ number_format($row['distance'], 2) }}</td><td class="text-end">{{ number_format($row['amount'], 2) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted">Fuel trend will appear after fuel logs are posted.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Fleet Status Board</div>
                <div class="card-body">
                    @forelse(($statusBoard ?? []) as $row)
                        <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row['status'] }}</span><strong>{{ number_format($row['count']) }}</strong></div>
                    @empty
                        <div class="text-muted">No vehicle status data.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Maintenance Due Board</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Vehicle</th><th>Due</th><th class="text-end">Odometer</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse(($maintenanceDue ?? []) as $item)
                                <tr><td>{{ $item['vehicle'] }}</td><td>{{ $item['due'] }}</td><td class="text-end">{{ number_format((float) $item['odometer'], 2) }}</td><td><span class="badge bg-warning text-dark">{{ $item['status'] }}</span></td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No upcoming maintenance due.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Document Expiry Alerts</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Vehicle</th><th>Document</th><th>Expiry</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse(($documentExpiry ?? []) as $doc)
                                <tr><td>{{ $doc['vehicle'] }}</td><td>{{ $doc['document_type'] }}</td><td>{{ $doc['expiry_date'] }}</td><td><span class="badge {{ $doc['status'] === 'expired' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $doc['status'] }}</span></td></tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No document expiry alerts.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Cost Trend</div>
                <div class="card-body">
                    @forelse(($costTrend ?? []) as $row)
                        <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row['month'] }}</span><strong>{{ number_format($row['amount'], 2) }}</strong></div>
                    @empty
                        <div class="text-muted">Cost trend will appear after maintenance logs are posted.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Vehicle Availability</div>
                <div class="card-body">
                    <div class="row g-2">
                        @forelse(($availability ?? []) as $row)
                            <div class="col-6"><div class="border rounded p-2"><div class="small text-muted">{{ $row['label'] }}</div><div class="fs-4 fw-bold">{{ number_format($row['count']) }}</div></div></div>
                        @empty
                            <div class="col-12 text-muted">No availability data.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-danger h-100">
                <div class="card-header fw-bold text-danger">Critical Alerts</div>
                <div class="card-body">
                    @forelse(($alerts ?? []) as $alert)
                        <div class="alert alert-{{ $alert['level'] ?? 'warning' }} py-2 mb-2">{{ $alert['message'] }}</div>
                    @empty
                        <div class="text-muted">No critical alerts.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold">Driver Performance</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Driver</th><th class="text-end">KM</th><th class="text-end">Avg KM/L</th><th class="text-end">Abnormal Fuel Logs</th></tr></thead>
                <tbody>
                    @forelse(($driverPerformance ?? []) as $row)
                        <tr><td>{{ $row['driver'] }}</td><td class="text-end">{{ number_format($row['km'], 2) }}</td><td class="text-end">{{ number_format($row['avg'], 2) }}</td><td class="text-end">{{ number_format($row['abnormal']) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No driver performance data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
