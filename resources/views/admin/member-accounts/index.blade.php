@extends('layouts.app')

@section('title', 'Member Accounts')
@section('page_title', 'Member Portal Accounts')

@section('content')
@if(session('generated_password'))
<div class="alert alert-success shadow-sm" role="alert">
    <strong>Account ban gaya — {{ session('generated_for') }}</strong><br>
    Username: <code>{{ session('generated_username') }}</code><br>
    Temporary Password: <code style="font-size:1.1rem;">{{ session('generated_password') }}</code>
    <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="navigator.clipboard.writeText('{{ session('generated_password') }}')">Copy</button>
    <div class="small text-muted mt-1">Ye password sirf ab dikhega. Note kar lein — member pehli login par khud badlega.</div>
</div>
@endif
<div class="card shadow-sm mb-3">
    <div class="card-header">Create Member Portal Account (Super Admin)</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.member-accounts.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3">
                <select name="member_id" class="form-select" required>
                    <option value="">Select Member</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}">{{ $m->member_code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email (optional)"></div>
            <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Password (blank = auto)"></div>
            <div class="col-md-3"><input type="password" name="password_confirmation" class="form-control" placeholder="Confirm (if manual)"></div>
            <div class="col-md-2 form-check ms-2"><input type="checkbox" class="form-check-input" name="force_password_change" value="1" id="forcep"><label class="form-check-label" for="forcep">Force change</label></div>
            <div class="col-md-2 form-check ms-2"><input type="checkbox" class="form-check-input" name="mark_mobile_verified" value="1" id="markmob"><label class="form-check-label" for="markmob">Mark mobile verified</label></div>
            <div class="col-md-2"><button class="btn btn-primary">Create Account</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Member Account Lifecycle</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Member</th><th>Mobile</th><th>User</th><th>Login Status</th><th>Portal</th><th>Mobile Verified</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($members as $m)
                <tr>
                    <td>{{ $m->member_code }} - {{ $m->name }}</td>
                    <td>{{ $m->mobile_number ?? '-' }}</td>
                    <td>{{ $m->user->username ?? '-' }}</td>
                    <td>
                        @if(! $m->user)
                            <span class="badge bg-danger">No account</span>
                        @elseif($m->user->last_login_at)
                            <span class="badge bg-success">Logged in {{ \Illuminate\Support\Carbon::parse($m->user->last_login_at)->format('Y-m-d') }}</span>
                        @else
                            <span class="badge bg-secondary">Never logged in</span>
                        @endif
                    </td>
                    <td>{{ $m->portal_enabled ? 'Enabled' : 'Disabled' }}</td>
                    <td>{{ $m->mobile_verified_at ? $m->mobile_verified_at->format('Y-m-d H:i') : '-' }}</td>
                    <td class="d-flex gap-1 flex-wrap">
                        <form method="POST" action="{{ route('admin.member-accounts.activate', $m->id) }}">@csrf<button class="btn btn-sm btn-outline-success">Activate</button></form>
                        <form method="POST" action="{{ route('admin.member-accounts.deactivate', $m->id) }}">@csrf<button class="btn btn-sm btn-outline-warning">Deactivate</button></form>
                        <form method="POST" action="{{ route('admin.member-accounts.reset', $m->id) }}">@csrf<button class="btn btn-sm btn-outline-danger">Reset Access</button></form>
                        <form method="POST" action="{{ route('admin.member-accounts.unlock-otp', $m->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Unlock OTP</button></form>
                        <form method="POST" action="{{ route('admin.member-accounts.mark-mobile-verified', $m->id) }}">@csrf<button class="btn btn-sm btn-outline-primary">Mark Mobile Verified</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
