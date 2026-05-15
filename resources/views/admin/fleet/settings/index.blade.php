@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Fleet Settings</h1>
    </div>

    <form method="POST" action="{{ route('admin.fleet.settings.update') }}" class="card">
        @csrf
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Alert Days</label>
                <input type="number" name="settings[alert_days]" value="{{ old('settings.alert_days', $settings['alert_days'] ?? 30) }}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Minimum Normal KM/L</label>
                <input type="number" step="0.01" name="settings[fuel_average_min_km_per_liter]" value="{{ old('settings.fuel_average_min_km_per_liter', $settings['fuel_average_min_km_per_liter'] ?? 4) }}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Maximum Normal KM/L</label>
                <input type="number" step="0.01" name="settings[fuel_average_max_km_per_liter]" value="{{ old('settings.fuel_average_max_km_per_liter', $settings['fuel_average_max_km_per_liter'] ?? 18) }}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Maintenance Due KM Buffer</label>
                <input type="number" name="settings[maintenance_due_km_buffer]" value="{{ old('settings.maintenance_due_km_buffer', $settings['maintenance_due_km_buffer'] ?? 500) }}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Maintenance Due Days Buffer</label>
                <input type="number" name="settings[maintenance_due_days_buffer]" value="{{ old('settings.maintenance_due_days_buffer', $settings['maintenance_due_days_buffer'] ?? 7) }}" class="form-control" required>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>
@endsection
