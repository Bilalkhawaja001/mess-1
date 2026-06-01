@extends('layouts.app')

@section('title', 'Mobile App Settings')
@section('page_title', 'Mobile App Settings')

@section('content')
<div class="container-fluid py-3">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Mobile App Control</h5>
                <small class="text-muted">Controls only the member app shell and public app settings payload.</small>
            </div>
            <code>/api/app-settings</code>
        </div>
        <form method="POST" action="{{ route('admin.app-settings.update') }}" class="card-body">
            @csrf
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="mobile_app_enabled" name="mobile_app_enabled" value="1" @checked(data_get($settings, 'mobile_app_enabled', true))>
                <label class="form-check-label fw-semibold" for="mobile_app_enabled">Enable mobile member app</label>
            </div>

            <h6>Feature switches</h6>
            <div class="row g-3 mb-4">
                @foreach(['dashboard' => 'Dashboard', 'bill' => 'Bill', 'payments' => 'Payments', 'statement' => 'Statement', 'menu' => 'Menu', 'complaint' => 'Complaint', 'profile' => 'Profile', 'notification' => 'Notification'] as $key => $label)
                    <div class="col-md-3 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="feature_{{ $key }}" name="features[{{ $key }}]" value="1" @checked(data_get($settings, 'features.'.$key, true))>
                                <label class="form-check-label" for="feature_{{ $key }}">{{ $label }}</label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <h6>Support</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Support Label</label>
                    <input class="form-control" name="support[label]" value="{{ old('support.label', data_get($settings, 'support.label')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input class="form-control" name="support[phone]" value="{{ old('support.phone', data_get($settings, 'support.phone')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Message</label>
                    <input class="form-control" name="support[message]" value="{{ old('support.message', data_get($settings, 'support.message')) }}">
                </div>
            </div>

            <h6>Android metadata</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Min Version</label>
                    <input class="form-control" name="android[min_version]" value="{{ old('android.min_version', data_get($settings, 'android.min_version')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Latest Version</label>
                    <input class="form-control" name="android[latest_version]" value="{{ old('android.latest_version', data_get($settings, 'android.latest_version')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Download URL</label>
                    <input class="form-control" name="android[download_url]" value="{{ old('android.download_url', data_get($settings, 'android.download_url')) }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="force_update" name="android[force_update]" value="1" @checked(data_get($settings, 'android.force_update', false))>
                        <label class="form-check-label" for="force_update">Force update</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-primary" type="submit">Save Settings</button>
                <span class="text-muted small">Public payload exposes feature flags/support metadata only.</span>
            </div>
        </form>
    </div>
</div>
@endsection
